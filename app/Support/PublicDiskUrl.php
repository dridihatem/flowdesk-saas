<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PublicDiskUrl
{
    /**
     * Absolute URL for a path on the public disk (served via the public/storage symlink).
     *
     * Uses the active HTTP request's scheme, host, port, and base path when available so links
     * work when APP_URL omits /public, a port, or a subdirectory (common with MAMP and similar).
     */
    public static function forPath(?string $relativePath): string
    {
        if ($relativePath === null || $relativePath === '') {
            return '';
        }

        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        $fallback = rtrim((string) config('app.url'), '/').'/storage/'.$relativePath;

        $req = request();
        if (! $req instanceof Request) {
            return $fallback;
        }

        $host = $req->getSchemeAndHttpHost();
        if ($host === '') {
            return $fallback;
        }

        $root = rtrim($host.$req->getBasePath(), '/');

        return $root.'/storage/'.$relativePath;
    }
}
