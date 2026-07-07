<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Services\CompanyThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, CompanyThemeService $themes): View
    {
        $user = $this->authorizeWorkspaceManagers($request);

        $settings = $themes->ensureSettings($user->company);
        $branding = is_array($settings->branding) ? $settings->branding : [];

        return view('settings.branding', [
            'branding' => array_merge([
                'tagline' => null,
                'support_email' => null,
                'contact_phone' => null,
                'website_url' => null,
            ], $branding),
        ]);
    }

    public function update(Request $request, CompanyThemeService $themes): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);

        $data = $request->validate([
            'tagline' => ['nullable', 'string', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website_url' => ['nullable', 'url', 'max:500'],
        ]);

        $settings = $themes->ensureSettings($user->company);
        $settings->branding = array_merge(is_array($settings->branding) ? $settings->branding : [], $data);
        $settings->save();

        return redirect()->route('settings.branding')->with('status', __('Branding saved.'));
    }
}
