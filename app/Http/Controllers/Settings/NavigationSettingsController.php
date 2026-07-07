<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceNavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavigationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_if(! $request->user()->hasRole('company_admin'), 403);
            abort_if(! $request->user()->company, 403);

            return $next($request);
        });
    }

    public function edit(Request $request, WorkspaceNavigationService $navigation): View
    {
        return view('settings.navigation', [
            'sections' => $navigation->manageableSectionsFor($request->user()->company),
        ]);
    }

    public function update(Request $request, WorkspaceNavigationService $navigation): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['nullable', 'array'],
            'order.*' => ['string', 'max:64'],
            'hidden' => ['nullable', 'array'],
            'hidden.*' => ['string', 'max:64'],
            'section_order' => ['nullable', 'array'],
            'section_order.*' => ['string', 'max:64'],
        ]);

        $navigation->savePreferences($request->user()->company, [
            'order' => $data['order'] ?? [],
            'hidden' => $data['hidden'] ?? [],
            'section_order' => $data['section_order'] ?? [],
        ]);

        return redirect()->route('settings.navigation')->with('status', __('settings_navigation_saved'));
    }
}
