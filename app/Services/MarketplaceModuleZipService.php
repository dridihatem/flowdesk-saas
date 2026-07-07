<?php

namespace App\Services;

use App\Models\MarketplaceModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class MarketplaceModuleZipService
{
    public function __construct(
        private ModuleSecurityScanner $security,
    ) {}

    public function store(MarketplaceModule $module, UploadedFile $archive): string
    {
        $this->validateArchive($archive);

        $zipPath = $archive->getRealPath();
        if (! is_string($zipPath) || $zipPath === '' || ! is_file($zipPath)) {
            throw new RuntimeException(__('modules_zip_invalid'));
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException(__('modules_zip_invalid'));
        }

        try {
            $rootPrefix = $this->detectRootPrefix($zip);
            if ($zip->getFromName($rootPrefix.'module.json') === false) {
                throw new RuntimeException(__('modules_manifest_missing'));
            }
            if ($zip->getFromName($rootPrefix.'views/index.blade.php') === false) {
                throw new RuntimeException(__('modules_views_index_missing'));
            }

            $this->security->scanZipArchive($zip, $rootPrefix);
        } finally {
            $zip->close();
        }

        $this->delete($module);

        $relative = 'marketplace-modules/'.$module->id.'/package.zip';
        Storage::disk('local')->putFileAs(
            'marketplace-modules/'.$module->id,
            $archive,
            'package.zip',
        );

        return $relative;
    }

    public function delete(MarketplaceModule $module): void
    {
        $path = $module->zip_path;
        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('local')->delete($path);
        Storage::disk('local')->deleteDirectory('marketplace-modules/'.$module->id);
    }

    public function absolutePath(MarketplaceModule $module): ?string
    {
        $path = $module->zip_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $absolute = Storage::disk('local')->path($path);

        return is_file($absolute) ? $absolute : null;
    }

    public function hasPackage(MarketplaceModule $module): bool
    {
        return $this->absolutePath($module) !== null;
    }

    private function validateArchive(UploadedFile $archive): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(__('modules_zip_extension_missing'));
        }

        if ($archive->getSize() > (int) config('modules.max_zip_bytes', 15_728_640)) {
            throw new RuntimeException(__('modules_zip_too_large'));
        }
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
}
