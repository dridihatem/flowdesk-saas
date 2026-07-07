<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InstalledModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ModuleRegistry
{
    public function __construct(
        private ModuleSecurityScanner $security,
    ) {}

    public function absolutePath(InstalledModule $module): string
    {
        return storage_path('app/'.$module->storage_path);
    }

    public function viewsPath(InstalledModule $module): string
    {
        return $this->absolutePath($module).DIRECTORY_SEPARATOR.'views';
    }

    public function resolveViewFile(InstalledModule $module, ?string $page = null): ?string
    {
        try {
            $page = $this->security->assertSafeViewPage($page);
        } catch (RuntimeException) {
            return null;
        }

        $candidates = $page === ''
            ? ['index.blade.php']
            : [
                str_replace('/', DIRECTORY_SEPARATOR, $page).'.blade.php',
                str_replace('/', DIRECTORY_SEPARATOR, $page).DIRECTORY_SEPARATOR.'index.blade.php',
            ];

        $base = $this->viewsPath($module);
        foreach ($candidates as $candidate) {
            $path = $base.DIRECTORY_SEPARATOR.$candidate;
            if (! is_file($path)) {
                continue;
            }

            try {
                $this->security->assertResolvedViewInsideBase($path, $base);
            } catch (RuntimeException) {
                return null;
            }

            return $path;
        }

        return null;
    }

    /**
     * Built-in module page views shipped with the app (when the zip has no override).
     */
    public function coreViewForPage(?string $page): ?string
    {
        $page = trim((string) ($page ?? ''), '/');

        return match ($page) {
            'settings' => 'modules.fallback.settings',
            default => null,
        };
    }

    /**
     * @return Collection<int, InstalledModule>
     */
    public function enabledForCompany(Company $company): Collection
    {
        return InstalledModule::query()
            ->where('company_id', $company->id)
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{slug: string, name: string, icon: string, route: string}>
     */
    public function navItemsFor(Company $company): array
    {
        return $this->enabledForCompany($company)
            ->map(fn (InstalledModule $module): array => [
                'slug' => $module->slug,
                'name' => $module->localizedName(),
                'icon' => $module->navIcon(),
                'route' => route('modules.show', ['slug' => $module->slug]),
            ])
            ->values()
            ->all();
    }

    public function deleteFiles(InstalledModule $module): void
    {
        $path = $this->absolutePath($module);
        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
    }
}
