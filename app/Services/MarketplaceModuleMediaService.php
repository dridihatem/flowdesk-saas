<?php

namespace App\Services;

use App\Models\MarketplaceModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MarketplaceModuleMediaService
{
    private const MAX_IMAGE_KB = 4096;

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function storeImage(MarketplaceModule $module, UploadedFile $file, string $kind): string
    {
        $this->validateImage($file);

        if (! in_array($kind, ['image', 'cover'], true)) {
            throw new RuntimeException(__('admin_marketplace_module_media_invalid_kind'));
        }

        $column = $kind === 'cover' ? 'cover_path' : 'image_path';
        $this->deletePath($module->{$column});

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $relative = 'marketplace-modules/'.$module->id.'/'.$kind.'.'.$extension;
        Storage::disk('public')->putFileAs(
            'marketplace-modules/'.$module->id,
            $file,
            $kind.'.'.$extension,
        );

        return $relative;
    }

    public function deleteImage(MarketplaceModule $module): void
    {
        $this->deletePath($module->image_path);
    }

    public function deleteCover(MarketplaceModule $module): void
    {
        $this->deletePath($module->cover_path);
    }

    public function deleteAll(MarketplaceModule $module): void
    {
        $this->deletePath($module->image_path);
        $this->deletePath($module->cover_path);
        Storage::disk('public')->deleteDirectory('marketplace-modules/'.$module->id);
    }

    private function deletePath(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function validateImage(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_IMAGE_KB * 1024) {
            throw new RuntimeException(__('admin_marketplace_module_media_too_large'));
        }

        $mime = $file->getMimeType();
        if (! is_string($mime) || ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException(__('admin_marketplace_module_media_invalid_type'));
        }
    }
}
