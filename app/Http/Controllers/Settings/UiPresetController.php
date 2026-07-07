<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUiPresetRequest;
use App\Services\WorkspaceCustomizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UiPresetController extends Controller
{
    public function store(StoreUiPresetRequest $request, WorkspaceCustomizationService $workspace): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $workspace->savePreset($company, $request->validated()['name']);

        return redirect()->route('settings.dashboard')->with('status', __('UI preset saved.'));
    }

    public function activate(Request $request, string $preset, WorkspaceCustomizationService $workspace): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $workspace->activatePreset($company, $preset);

        return redirect()->route('settings.dashboard')->with('status', __('Preset applied.'));
    }

    public function destroy(Request $request, string $preset, WorkspaceCustomizationService $workspace): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $workspace->deletePreset($company, $preset);

        return redirect()->route('settings.dashboard')->with('status', __('Preset removed.'));
    }
}
