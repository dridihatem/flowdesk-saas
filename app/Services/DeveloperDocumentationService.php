<?php

namespace App\Services;

class DeveloperDocumentationService
{
    /**
     * @return array{stack: list<string>, repo_guides: list<array<string, string>>, sections: list<array<string, mixed>>, workflows: list<array<string, mixed>>}
     */
    public function catalog(): array
    {
        return config('developer-docs', []);
    }

    /**
     * @return list<array{id: string, icon: string, label: string}>
     */
    public function sectionNav(): array
    {
        $labels = [
            'overview' => __('Project overview'),
            'app' => __('Application (app/)'),
            'routes' => __('Routes & middleware'),
            'tenancy' => __('Multi-tenancy'),
            'ai' => __('AI & Nova'),
            'modules' => __('Modules marketplace'),
            'billing' => __('Billing & plans'),
            'frontend' => __('Frontend assets'),
            'database' => __('Database'),
            'testing' => __('Testing'),
        ];

        return collect($this->catalog()['sections'] ?? [])
            ->map(fn (array $section): array => [
                'id' => (string) ($section['id'] ?? ''),
                'icon' => (string) ($section['icon'] ?? 'fa-folder'),
                'label' => $labels[$section['id'] ?? ''] ?? ucfirst((string) ($section['id'] ?? '')),
            ])
            ->filter(fn (array $row): bool => $row['id'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function section(string $id): ?array
    {
        foreach ($this->catalog()['sections'] ?? [] as $section) {
            if (($section['id'] ?? '') === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @return list<array{file: string, title: string, topic: string, exists: bool, path: string}>
     */
    public function repoGuides(): array
    {
        $base = base_path();

        return collect($this->catalog()['repo_guides'] ?? [])
            ->map(function (array $guide) use ($base): array {
                $file = (string) ($guide['file'] ?? '');
                $path = $base.DIRECTORY_SEPARATOR.$file;

                return [
                    'file' => $file,
                    'title' => (string) ($guide['title'] ?? $file),
                    'topic' => (string) ($guide['topic'] ?? ''),
                    'exists' => is_file($path),
                    'path' => $path,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Flatten a nested tree for display.
     *
     * @param  array<string, mixed>  $tree
     * @return list<array{path: string, hint: string|null, depth: int}>
     */
    public function flattenTree(array $tree, int $depth = 0): array
    {
        $rows = [];

        foreach ($tree as $key => $value) {
            $path = (string) $key;

            if (is_array($value)) {
                $childPaths = array_keys($value);
                $isAssoc = $childPaths !== range(0, count($value) - 1);
                $hint = $isAssoc ? null : (string) ($value[0] ?? '');

                if (! $isAssoc && count($value) === 1 && is_string($value[0] ?? null)) {
                    $rows[] = ['path' => $path, 'hint' => $hint, 'depth' => $depth];

                    continue;
                }

                if (! $isAssoc) {
                    $rows[] = ['path' => $path, 'hint' => null, 'depth' => $depth];
                    foreach ($value as $child) {
                        if (is_string($child)) {
                            $rows[] = ['path' => $child, 'hint' => null, 'depth' => $depth + 1];
                        }
                    }

                    continue;
                }

                $rows[] = ['path' => $path, 'hint' => null, 'depth' => $depth];
                $rows = array_merge($rows, $this->flattenTree($value, $depth + 1));
            } else {
                $rows[] = [
                    'path' => $path,
                    'hint' => is_string($value) ? $value : null,
                    'depth' => $depth,
                ];
            }
        }

        return $rows;
    }
}
