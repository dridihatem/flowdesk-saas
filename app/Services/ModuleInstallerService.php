<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InstalledModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use ZipArchive;

class ModuleInstallerService
{
    public function __construct(
        private ModuleRegistry $registry,
        private ModuleSecurityScanner $security,
    ) {}

    public function installFromZip(Company $company, UploadedFile $archive): InstalledModule
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(__('modules_zip_extension_missing'));
        }

        if ($archive->getSize() > (int) config('modules.max_zip_bytes', 15_728_640)) {
            throw new RuntimeException(__('modules_zip_too_large'));
        }

        $zipPath = $archive->getRealPath();
        if (! is_string($zipPath) || $zipPath === '' || ! is_file($zipPath)) {
            throw new RuntimeException(__('modules_zip_invalid'));
        }

        return $this->installFromArchivePath($company, $zipPath);
    }

    public function installFromStoredZip(Company $company, string $absoluteZipPath): InstalledModule
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(__('modules_zip_extension_missing'));
        }

        if (! is_file($absoluteZipPath)) {
            throw new RuntimeException(__('modules_zip_invalid'));
        }

        if (filesize($absoluteZipPath) > (int) config('modules.max_zip_bytes', 15_728_640)) {
            throw new RuntimeException(__('modules_zip_too_large'));
        }

        return $this->installFromArchivePath($company, $absoluteZipPath);
    }

    private function installFromArchivePath(Company $company, string $zipPath): InstalledModule
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException(__('modules_zip_invalid'));
        }

        try {
            $rootPrefix = $this->detectRootPrefix($zip);
            $manifestIndex = $rootPrefix.'module.json';
            $manifestRaw = $zip->getFromName($manifestIndex);
            if ($manifestRaw === false) {
                throw new RuntimeException(__('modules_manifest_missing'));
            }

            $manifest = $this->parseManifest($manifestRaw);
            $slug = $manifest['slug'];

            if (InstalledModule::query()->where('company_id', $company->id)->where('slug', $slug)->exists()) {
                throw new RuntimeException(__('modules_already_installed', ['slug' => $slug]));
            }

            if ($zip->getFromName($rootPrefix.'views/index.blade.php') === false) {
                throw new RuntimeException(__('modules_views_index_missing'));
            }

            $this->security->scanZipArchive($zip, $rootPrefix);

            $relativeStorage = 'workspaces/'.$company->id.'/modules/'.$slug;
            $target = storage_path('app/'.$relativeStorage);
            if (is_dir($target)) {
                File::deleteDirectory($target);
            }
            File::ensureDirectoryExists($target);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (! is_string($entry) || ! str_starts_with($entry, $rootPrefix)) {
                    continue;
                }
                $relative = substr($entry, strlen($rootPrefix));
                if ($relative === '' || str_ends_with($relative, '/')) {
                    continue;
                }
                $dest = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
                File::ensureDirectoryExists(dirname($dest));
                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    throw new RuntimeException(__('modules_zip_extract_failed'));
                }
                file_put_contents($dest, $contents);
            }

            $module = InstalledModule::query()->create([
                'company_id' => $company->id,
                'slug' => $slug,
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'description' => $manifest['description'] ?? null,
                'author' => $manifest['author'] ?? null,
                'manifest' => $manifest['raw'],
                'storage_path' => $relativeStorage,
                'is_enabled' => true,
                'installed_at' => now(),
            ]);

            $this->runModuleMigrations($module);

            return $module;
        } finally {
            $zip->close();
        }
    }

    public function uninstall(InstalledModule $module): void
    {
        // Roll back the module's migrations (drops its tables) unless another
        // workspace still uses the same module — tables are shared by slug.
        $usedElsewhere = InstalledModule::query()
            ->withoutGlobalScopes()
            ->where('slug', $module->slug)
            ->whereKeyNot($module->getKey())
            ->exists();

        if (! $usedElsewhere) {
            $this->rollbackModuleMigrations($module);
        }

        $this->registry->deleteFiles($module);
        $module->delete();
    }

    public function setEnabled(InstalledModule $module, bool $enabled): void
    {
        $module->update(['is_enabled' => $enabled]);
    }

    private function runModuleMigrations(InstalledModule $module): void
    {
        $migrationDir = $this->moduleMigrationsPath($module);
        if ($migrationDir === null) {
            return;
        }

        Artisan::call('migrate', [
            '--force' => true,
            '--path' => $migrationDir,
            '--realpath' => true,
        ]);
    }

    private function rollbackModuleMigrations(InstalledModule $module): void
    {
        $migrationDir = $this->moduleMigrationsPath($module);
        if ($migrationDir === null) {
            return;
        }

        try {
            // migrate:reset rolls back every ran migration found in the path,
            // regardless of batch — unlike migrate:rollback (last batch only).
            Artisan::call('migrate:reset', [
                '--force' => true,
                '--path' => $migrationDir,
                '--realpath' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('module_uninstall_rollback_failed', [
                'module' => $module->slug,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function moduleMigrationsPath(InstalledModule $module): ?string
    {
        $dir = $this->registry->absolutePath($module).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        return is_dir($dir) ? $dir : null;
    }

    private function detectRootPrefix(ZipArchive $zip): string
    {
        $manifestAtRoot = false;
        $folders = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name)) {
                continue;
            }
            if ($name === 'module.json') {
                $manifestAtRoot = true;
            }
            if (preg_match('#^([^/]+)/module\.json$#', $name, $m)) {
                $folders[$m[1]] = true;
            }
        }

        if ($manifestAtRoot) {
            return '';
        }

        if (count($folders) === 1) {
            return array_key_first($folders).'/';
        }

        throw new RuntimeException(__('modules_manifest_missing'));
    }

    /**
     * @return array{slug: string, name: string, version: string, description?: string, author?: string, raw: array<string, mixed>}
     */
    private function parseManifest(string $raw): array
    {
        try {
            $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException(__('modules_manifest_invalid_json'));
        }

        if (! is_array($data)) {
            throw new RuntimeException(__('modules_manifest_invalid_json'));
        }

        $slug = isset($data['slug']) && is_string($data['slug']) ? trim($data['slug']) : '';
        $name = isset($data['name']) && is_string($data['name']) ? trim($data['name']) : '';
        $version = isset($data['version']) && is_string($data['version']) ? trim($data['version']) : '1.0.0';

        if ($slug === '' || ! preg_match('/^[a-z0-9][a-z0-9_-]{1,63}$/', $slug)) {
            throw new RuntimeException(__('modules_manifest_invalid_slug'));
        }
        if ($name === '') {
            throw new RuntimeException(__('modules_manifest_invalid_name'));
        }

        return [
            'slug' => $slug,
            'name' => mb_substr($name, 0, 255),
            'version' => mb_substr($version, 0, 50),
            'description' => isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            'author' => isset($data['author']) && is_string($data['author']) ? $data['author'] : null,
            'raw' => $data,
        ];
    }
}
