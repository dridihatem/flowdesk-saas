<?php

namespace App\Services;

use App\Models\InstalledModule;

class ModuleTranslationService
{
    /** @var array<string, array<string, string>> */
    private static array $lines = [];

    public function __construct(
        private ModuleRegistry $registry,
    ) {}

    public function langDirectory(InstalledModule $module): string
    {
        return $this->registry->absolutePath($module).DIRECTORY_SEPARATOR.'lang';
    }

    /**
     * Load module strings for the current request locale.
     */
    public function register(InstalledModule $module, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        self::$lines[$this->cacheKey($module, $locale)] = $this->loadLines($module, $locale);
    }

    public function translate(InstalledModule $module, string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = $this->cacheKey($module, $locale);
        $lines = self::$lines[$cacheKey] ?? $this->loadLines($module, $locale);
        self::$lines[$cacheKey] = $lines;

        $text = $lines[$key] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $text = str_replace(':'.$placeholder, (string) $value, $text);
        }

        return $text;
    }

    /**
     * @return array<string, string>
     */
    public function loadLines(InstalledModule $module, string $locale): array
    {
        $dir = $this->langDirectory($module);
        if (! is_dir($dir)) {
            return [];
        }

        $path = $this->resolveLocaleFile($dir, $locale);
        if ($path === null) {
            return [];
        }

        $raw = file_get_contents($path);
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function cacheKey(InstalledModule $module, string $locale): string
    {
        return $module->id.'|'.$locale;
    }

    private function resolveLocaleFile(string $dir, string $locale): ?string
    {
        $allowed = config('flowdesk.locales', ['en']);
        $candidates = in_array($locale, $allowed, true)
            ? [$locale, 'en']
            : ['en'];

        foreach (array_unique($candidates) as $code) {
            $path = $dir.DIRECTORY_SEPARATOR.$code.'.json';
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
