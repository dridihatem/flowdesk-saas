<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDashboardLayoutRequest;
use App\Services\WorkspaceCustomizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardLayoutController extends Controller
{
    public function edit(Request $request, WorkspaceCustomizationService $workspace): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        return view('settings.dashboard', [
            'widgets' => $workspace->resolvedWidgets($company),
            'presets' => $workspace->listPresets($company),
        ]);
    }

    public function update(UpdateDashboardLayoutRequest $request, WorkspaceCustomizationService $workspace): RedirectResponse|JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $widgets = collect($request->input('widgets', []))
            ->filter(fn ($w) => is_array($w))
            ->map(function (array $w, int $i) {
                return [
                    'key' => (string) ($w['key'] ?? ''),
                    'enabled' => (bool) ($w['enabled'] ?? true),
                    'order' => isset($w['order']) ? (int) $w['order'] : $i,
                ];
            })
            ->values()
            ->all();

        $workspace->saveDashboardLayout($company, $widgets);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'ok']);
        }

        return redirect()->route('settings.dashboard')->with('status', __('Dashboard layout saved.'));
    }
}
