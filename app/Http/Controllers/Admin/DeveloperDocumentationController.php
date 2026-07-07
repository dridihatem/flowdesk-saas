<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeveloperDocumentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeveloperDocumentationController extends Controller
{
    public function index(Request $request, DeveloperDocumentationService $docs): View
    {
        $sections = $docs->sectionNav();
        $activeId = (string) $request->query('section', $sections[0]['id'] ?? 'overview');
        $activeSection = $docs->section($activeId) ?? $docs->section('overview');

        $trees = [];
        if (is_array($activeSection)) {
            foreach ($activeSection['tree'] ?? [] as $root => $tree) {
                if (is_array($tree)) {
                    $trees[] = [
                        'root' => (string) $root,
                        'rows' => $docs->flattenTree([$root => $tree]),
                    ];
                } else {
                    $trees[] = [
                        'root' => (string) $root,
                        'rows' => [
                            [
                                'path' => (string) $root,
                                'hint' => is_string($tree) ? $tree : null,
                                'depth' => 0,
                            ],
                        ],
                    ];
                }
            }
        }

        return view('admin.developer-docs.index', [
            'sections' => $sections,
            'activeId' => $activeId,
            'activeSection' => $activeSection,
            'trees' => $trees,
            'stack' => $docs->catalog()['stack'] ?? [],
            'repoGuides' => $docs->repoGuides(),
            'workflows' => $docs->catalog()['workflows'] ?? [],
        ]);
    }
}
