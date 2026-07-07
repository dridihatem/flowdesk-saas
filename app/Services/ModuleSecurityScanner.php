<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ModuleSecurityScanner
{
    /**
     * Executable magic signatures (prefix bytes).
     *
     * @var list<array{bytes: string, label: string}>
     */
    private const EXECUTABLE_SIGNATURES = [
        ['bytes' => "\x7FELF", 'label' => 'ELF'],
        ['bytes' => 'MZ', 'label' => 'PE/EXE'],
        ['bytes' => "\xCA\xFE\xBA\xBE", 'label' => 'Mach-O fat'],
        ['bytes' => "\xFE\xED\xFA\xCE", 'label' => 'Mach-O'],
        ['bytes' => "\xFE\xED\xFA\xCF", 'label' => 'Mach-O 64'],
        ['bytes' => '#!/', 'label' => 'shell script'],
    ];

    /**
     * Forbidden PHP constructs in Blade views and migrations.
     *
     * @var list<string>
     */
    private const DANGEROUS_PHP_PATTERNS = [
        '/\beval\s*\(/i',
        '/\bassert\s*\(\s*[\'"]/i',
        '/\b(create_function|pcntl_\w+)\s*\(/i',
        '/\b(exec|shell_exec|system|passthru|proc_open|popen)\s*\(/i',
        '/\b(preg_replace|mb_ereg_replace)\s*\([^)]*\/e[\'"]/i',
        '/\b(include|require|include_once|require_once)\s*[\(\$]/i',
        '/\b(file_put_contents|fopen|unlink|rmdir|chmod|chown|rename|copy)\s*\(/i',
        '/\b(move_uploaded_file|symlink|link)\s*\(/i',
        '/\b`[^`]+`/i',
        '/\bDB::(unprepared|statement)\s*\(/i',
        '/\bArtisan::call\s*\(/i',
        '/\bIlluminate\\\\Support\\\\Facades\\\\Process::/i',
        '/\bIlluminate\\\\Support\\\\Facades\\\\Http::/i',
        '/\b(guzzlehttp|curl_exec|fsockopen|stream_socket_client)\b/i',
        '/\b(base64_decode\s*\(\s*.*\)\s*;?\s*(eval|assert|preg_replace))/is',
        '/\bDROP\s+DATABASE\b/i',
        '/\bINTO\s+OUTFILE\b/i',
        '/\bLOAD_FILE\s*\(/i',
        '/\bGRANT\s+/i',
        '/\bREVOKE\s+/i',
    ];

    /**
     * Extra restrictions for Blade views (modules run in app context).
     *
     * @var list<string>
     */
    private const BLADE_ONLY_PATTERNS = [
        '/\bDB::raw\s*\(/i',
        '/\bMail::/i',
        '/\bNotification::/i',
        '/\bStorage::/i',
        '/\bFile::(put|append|prepend|copy|move|delete)/i',
        '/\$_((GET|POST|REQUEST|COOKIE|SERVER|FILES|ENV)\b)/i',
        '/\bphpinfo\s*\(/i',
        '/\bputenv\s*\(/i',
        '/\bini_set\s*\(\s*[\'"]disable_functions/i',
    ];

    public function scanZipArchive(ZipArchive $zip, string $rootPrefix): void
    {
        $maxFiles = (int) config('modules.max_files', 250);
        $maxUncompressed = (int) config('modules.max_uncompressed_bytes', 52_428_800);
        $maxSingle = (int) config('modules.max_single_file_bytes', 5_242_880);

        $fileCount = 0;
        $totalUncompressed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (! is_string($entry)) {
                continue;
            }

            $this->assertEntryPathSafe($entry);

            if ($rootPrefix !== '' && ! str_starts_with($entry, $rootPrefix)) {
                continue;
            }

            $relative = $rootPrefix === '' ? $entry : substr($entry, strlen($rootPrefix));
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            if ($this->isBlockedPathSegment($relative)) {
                throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
            }

            $stat = $zip->statIndex($i);
            if (! is_array($stat)) {
                throw new RuntimeException(__('modules_zip_invalid'));
            }

            if ($this->isSymlinkEntry($stat)) {
                throw new RuntimeException(__('modules_zip_symlink_forbidden', ['path' => $relative]));
            }

            $uncompressed = (int) ($stat['size'] ?? 0);
            if ($uncompressed > $maxSingle) {
                throw new RuntimeException(__('modules_zip_file_too_large', ['path' => $relative]));
            }

            $totalUncompressed += $uncompressed;
            if ($totalUncompressed > $maxUncompressed) {
                throw new RuntimeException(__('modules_zip_uncompressed_too_large'));
            }

            $fileCount++;
            if ($fileCount > $maxFiles) {
                throw new RuntimeException(__('modules_zip_too_many_files'));
            }

            $this->assertRelativePathAllowed($relative);

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                throw new RuntimeException(__('modules_zip_extract_failed'));
            }

            if ($this->looksLikeExecutable($contents)) {
                throw new RuntimeException(__('modules_zip_executable_detected', ['path' => $relative]));
            }

            $this->scanFileContents($relative, $contents);
        }
    }

    public function scanFileContents(string $relativePath, string $contents): void
    {
        $isBladeView = (bool) preg_match('#^views/.+\.blade\.php$#i', $relativePath);
        $isMigration = (bool) preg_match('#^database/migrations/.+\.php$#i', $relativePath);
        $isPhp = (bool) preg_match('/\.php$/i', $relativePath);

        if (! $isPhp) {
            $this->scanNonPhpAsset($relativePath, $contents);

            return;
        }

        if (! $isBladeView && ! $isMigration) {
            throw new RuntimeException(__('modules_zip_php_not_allowed'));
        }

        $normalized = $this->stripBladeDirectives($contents);

        foreach (self::DANGEROUS_PHP_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized)) {
                throw new RuntimeException(__('modules_zip_blocked_content', [
                    'file' => $relativePath,
                    'reason' => __('modules_security_reason_dangerous_php'),
                ]));
            }
        }

        if ($isBladeView) {
            foreach (self::BLADE_ONLY_PATTERNS as $pattern) {
                if (preg_match($pattern, $normalized)) {
                    throw new RuntimeException(__('modules_zip_blocked_content', [
                        'file' => $relativePath,
                        'reason' => __('modules_security_reason_blade_restriction'),
                    ]));
                }
            }
        }

        if ($isMigration) {
            $this->scanMigrationSql($relativePath, $normalized);
        }
    }

    /**
     * Reject path traversal and absolute paths inside zip metadata.
     */
    public function assertEntryPathSafe(string $entry): void
    {
        if (
            str_contains($entry, "\0")
            || str_contains($entry, '..')
            || str_starts_with($entry, '/')
            || str_starts_with($entry, '\\')
            || preg_match('#^[a-zA-Z]:[/\\\\]#', $entry) === 1
        ) {
            throw new RuntimeException(__('modules_zip_unsafe_path'));
        }
    }

    /**
     * Safe page slug for /modules/{slug}/{page} resolution.
     */
    public function assertSafeViewPage(?string $page): string
    {
        $page = trim((string) $page, '/');

        if ($page === '') {
            return '';
        }

        if (
            str_contains($page, '..')
            || str_contains($page, "\0")
            || preg_match('#^[a-zA-Z0-9][a-zA-Z0-9_\-/]*$#', $page) !== 1
        ) {
            throw new RuntimeException(__('modules_view_path_unsafe'));
        }

        foreach (explode('/', $page) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException(__('modules_view_path_unsafe'));
            }
        }

        return $page;
    }

    /**
     * Resolved view file must stay inside the module views directory.
     */
    public function assertResolvedViewInsideBase(string $resolvedFile, string $viewsBase): void
    {
        $realFile = realpath($resolvedFile);
        $realBase = realpath($viewsBase);

        if ($realFile === false || $realBase === false) {
            throw new RuntimeException(__('modules_view_path_unsafe'));
        }

        $base = rtrim($realBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with($realFile, $base)) {
            throw new RuntimeException(__('modules_view_path_unsafe'));
        }
    }

    private function assertRelativePathAllowed(string $relative): void
    {
        $relative = str_replace('\\', '/', $relative);
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        $blockedExtensions = config('modules.blocked_extensions', []);
        if ($extension !== '' && in_array($extension, $blockedExtensions, true)) {
            throw new RuntimeException(__('modules_zip_forbidden_extension', ['ext' => $extension]));
        }

        $isPhp = $extension === 'php';
        $isBladeView = (bool) preg_match('#^views/.+\.blade\.php$#i', $relative);
        $isMigration = (bool) preg_match('#^database/migrations/.+\.php$#i', $relative);

        if ($isPhp && ! $isBladeView && ! $isMigration) {
            throw new RuntimeException(__('modules_zip_php_not_allowed'));
        }

        if (! $isPhp) {
            $allowedAssets = config('modules.allowed_asset_extensions', []);
            if (! in_array($extension, $allowedAssets, true)) {
                throw new RuntimeException(__('modules_zip_forbidden_extension', ['ext' => $extension ?: '?']));
            }
        }

        $topLevel = explode('/', $relative, 2)[0];

        if ($relative !== 'module.json' && ! in_array($topLevel, config('modules.allowed_root_folders', []), true)) {
            throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
        }

        if ($topLevel === 'database') {
            if (! preg_match('#^database/migrations/\d{4}_\d{2}_\d{2}_\d{6}_[a-zA-Z0-9_]+\.php$#', $relative)) {
                throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
            }
        }

        if ($topLevel === 'views' && ! preg_match('#^views(/[a-zA-Z0-9_\-]+)*\.blade\.php$#', $relative)) {
            throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
        }

        if ($topLevel === 'assets' && ! preg_match('#^assets(/[a-zA-Z0-9_\-./]+)*\.[a-z0-9]+$#i', $relative)) {
            throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
        }

        if ($topLevel === 'lang') {
            $allowedLocales = implode('|', config('flowdesk.locales', ['en']));
            if (! preg_match('#^lang/('.$allowedLocales.')\.json$#', $relative)) {
                throw new RuntimeException(__('modules_zip_forbidden_path', ['path' => $relative]));
            }
        }
    }

    private function isBlockedPathSegment(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);
        $segments = explode('/', $relative);
        $blocked = config('modules.blocked_path_segments', []);

        foreach ($segments as $segment) {
            if (in_array($segment, $blocked, true)) {
                return true;
            }
            if (str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function isSymlinkEntry(array $stat): bool
    {
        $attrs = (int) ($stat['external_attributes'] ?? $stat['external_attrs'] ?? 0);

        // UNIX symlink type nibble in external file attributes.
        return (($attrs >> 16) & 0xF000) === 0xA000;
    }

    private function looksLikeExecutable(string $contents): bool
    {
        if ($contents === '') {
            return false;
        }

        foreach (self::EXECUTABLE_SIGNATURES as $signature) {
            if (str_starts_with($contents, $signature['bytes'])) {
                return true;
            }
        }

        return false;
    }

    private function scanNonPhpAsset(string $relativePath, string $contents): void
    {
        if ($this->looksLikeExecutable($contents)) {
            throw new RuntimeException(__('modules_zip_executable_detected', ['path' => $relativePath]));
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if ($extension === 'js' && preg_match('/\b(eval|Function)\s*\(/i', $contents)) {
            throw new RuntimeException(__('modules_zip_blocked_content', [
                'file' => $relativePath,
                'reason' => __('modules_security_reason_dangerous_js'),
            ]));
        }
    }

    private function scanMigrationSql(string $relativePath, string $contents): void
    {
        $sqlPatterns = [
            '/\bTRUNCATE\s+TABLE\s+(?!module_)/i',
            '/\bDROP\s+TABLE\s+(?!module_)/i',
            '/\bALTER\s+TABLE\s+(?!module_)/i',
        ];

        if (preg_match('/\bDB::(select|insert|update|delete)\s*\(\s*[\'"][^\'"]*[\'"]\s*\./i', $contents)) {
            throw new RuntimeException(__('modules_zip_blocked_content', [
                'file' => $relativePath,
                'reason' => __('modules_security_reason_sql_injection'),
            ]));
        }

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $contents)) {
                throw new RuntimeException(__('modules_zip_blocked_content', [
                    'file' => $relativePath,
                    'reason' => __('modules_security_reason_migration_scope'),
                ]));
            }
        }
    }

    private function stripBladeComments(string $contents): string
    {
        return (string) preg_replace('/{{--[\s\S]*?--}}/m', '', $contents);
    }

    /**
     * Blade @include of app partials is allowed; strip so PHP include() rules do not false-positive.
     */
    private function stripBladeDirectives(string $contents): string
    {
        $contents = $this->stripBladeComments($contents);

        return (string) preg_replace('/@include(?:When|Unless|First)?\s*\([^)]*\)/', '', $contents);
    }
}
