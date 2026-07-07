<?php

namespace App\Models\Concerns;

trait ParsesModuleManifest
{
    public function isBundle(): bool
    {
        $type = $this->manifest['type'] ?? null;
        if ($type === 'bundle') {
            return true;
        }

        $includes = $this->manifest['includes_modules'] ?? null;

        return is_array($includes) && $includes !== [];
    }

    /**
     * @return array{slug: string, name: string}|null
     */
    public function partOfBundle(): ?array
    {
        $part = $this->manifest['part_of_bundle'] ?? null;
        if (! is_array($part)) {
            return null;
        }
        $slug = isset($part['slug']) && is_string($part['slug']) ? trim($part['slug']) : '';
        if ($slug === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => is_string($part['name'] ?? null) && $part['name'] !== ''
                ? $part['name']
                : $slug,
        ];
    }

    /**
     * Sub-pages for module navigation (manifest `pages` + auto Settings tab).
     *
     * @return list<array{slug: string, label: string, route: string}>
     */
    public function navigationPages(): array
    {
        $pages = $this->manifest['pages'] ?? null;
        $out = [];

        if (! is_array($pages) || $pages === []) {
            $out[] = [
                'slug' => '',
                'label' => __('Overview'),
                'route' => route('modules.show', $this->slug),
            ];
        } else {
            foreach ($pages as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $pageSlug = isset($entry['slug']) && is_string($entry['slug']) ? trim($entry['slug']) : '';
                $out[] = [
                    'slug' => $pageSlug,
                    'label' => $this->resolvePageLabel($entry, $pageSlug),
                    'route' => $pageSlug === ''
                        ? route('modules.show', $this->slug)
                        : route('modules.show', ['slug' => $this->slug, 'page' => $pageSlug]),
                ];
            }
        }

        if (! $this->navigationHasPage($out, 'settings')) {
            $out[] = $this->settingsNavigationEntry();
        }

        return $out;
    }

    /**
     * @param  list<array{slug: string, label: string, route: string}>  $pages
     */
    private function navigationHasPage(array $pages, string $slug): bool
    {
        foreach ($pages as $page) {
            if (($page['slug'] ?? '') === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{slug: string, label: string, route: string}
     */
    private function settingsNavigationEntry(): array
    {
        return [
            'slug' => 'settings',
            'label' => module_label($this, 'nav_settings', 'module_nav_settings'),
            'route' => route('modules.show', ['slug' => $this->slug, 'page' => 'settings']),
        ];
    }

    /**
     * Modules included in this bundle zip (manifest `includes_modules`).
     *
     * @return list<array{slug: string, name: string, required: bool, paid: bool, price_hint: ?string, standalone_zip: bool}>
     */
    public function includesModules(): array
    {
        return $this->normalizeModuleRefs($this->manifest['includes_modules'] ?? [], true);
    }

    /**
     * Optional related / add-on modules (manifest `related_modules`).
     *
     * @return list<array{slug: string, name: string, required: bool, paid: bool, price_hint: ?string, included: bool}>
     */
    public function relatedModules(): array
    {
        $refs = $this->normalizeModuleRefs($this->manifest['related_modules'] ?? [], false);

        return array_map(static function (array $row): array {
            $row['included'] = (bool) ($row['included'] ?? false);

            return $row;
        }, $refs);
    }

    /**
     * @param  array<int, mixed>  $entries
     * @return list<array{slug: string, name: string, required: bool, paid: bool, price_hint: ?string, standalone_zip: bool}>
     */
    private function normalizeModuleRefs(array $entries, bool $withStandalone): array
    {
        $out = [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $slug = isset($entry['slug']) && is_string($entry['slug']) ? trim($entry['slug']) : '';
            if ($slug === '') {
                continue;
            }
            $name = is_string($entry['name'] ?? null) && trim($entry['name']) !== ''
                ? trim($entry['name'])
                : $slug;
            $row = [
                'slug' => $slug,
                'name' => $name,
                'required' => (bool) ($entry['required'] ?? false),
                'paid' => (bool) ($entry['paid'] ?? false),
                'price_hint' => is_string($entry['price_hint'] ?? null) && $entry['price_hint'] !== ''
                    ? $entry['price_hint']
                    : null,
            ];
            if ($withStandalone) {
                $row['standalone_zip'] = (bool) ($entry['standalone_zip'] ?? true);
            }
            if (isset($entry['included'])) {
                $row['included'] = (bool) $entry['included'];
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolvePageLabel(array $entry, string $pageSlug): string
    {
        if (isset($entry['label_key']) && is_string($entry['label_key']) && $entry['label_key'] !== '') {
            $translated = module_trans($this, $entry['label_key']);
            if ($translated !== $entry['label_key']) {
                return $translated;
            }
        }

        if (isset($entry['label']) && is_string($entry['label']) && $entry['label'] !== '') {
            return $entry['label'];
        }

        return $pageSlug === '' ? __('Overview') : ucfirst(str_replace(['-', '_'], ' ', $pageSlug));
    }
}
