<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\CompanyThemeService;
use App\Services\PlanLimitService;
use App\Services\ProfileHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(
        Request $request,
        CompanyThemeService $themeService,
        ProfileHubService $profileHub,
        PlanLimitService $planLimits,
    ): View {
        $user = $request->user();
        $showWorkspaceProfile = $user->company && $user->hasAnyRole(['company_admin', 'team_member']);
        $profileMarketing = [];
        $profileEmbedBaseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
        $profileHasApiToken = false;
        $profileApiTokenHint = null;
        $profileCompany = null;
        $profileBranding = [];
        $profileTheme = [];

        if ($showWorkspaceProfile) {
            $company = $user->company;
            $profileCompany = $company;
            $settings = $themeService->ensureSettings($company);
            $profileMarketing = is_array($settings->marketing) ? $settings->marketing : [];
            $profileBranding = array_merge([
                'tagline' => null,
                'support_email' => null,
                'contact_phone' => null,
                'website_url' => null,
            ], is_array($settings->branding) ? $settings->branding : []);
            $profileTheme = $themeService->themeFor($company, $user);
            $profileHasApiToken = $company->api_token_hash !== null;
            $profileApiTokenHint = $company->api_token_hint;
        }

        $profileRevealedToken = $request->session()->pull('flowdesk_company_api_token_plain');

        // The stored token (encrypted) so it can always be shown and injected
        // into the data-token snippets; null for legacy tokens (hash only).
        $profileApiTokenPlain = $profileRevealedToken
            ?: ($showWorkspaceProfile ? $user->company->apiTokenPlain() : null);

        $profileGroups = $profileHub->linkGroups($user, $planLimits);
        $profileNavGroups = collect($profileHub->groupOrder())
            ->map(fn (string $key) => [
                'key' => $key,
                'label' => $profileHub->groupLabel($key),
                'anchor' => 'profile-group-'.$key,
            ])
            ->all();

        return view('profile.edit', [
            'user' => $user,
            'showWorkspaceProfile' => $showWorkspaceProfile,
            'showCompanyMarketingOnProfile' => $showWorkspaceProfile,
            'profileMarketing' => $profileMarketing,
            'profileEmbedBaseUrl' => $profileEmbedBaseUrl,
            'profileHasApiToken' => $profileHasApiToken,
            'profileApiTokenHint' => $profileApiTokenHint,
            'profileRevealedToken' => $profileRevealedToken,
            'profileApiTokenPlain' => $profileApiTokenPlain,
            'profileCompany' => $profileCompany,
            'profileBranding' => $profileBranding,
            'profileTheme' => $profileTheme,
            'profileGroups' => $profileGroups,
            'profileNavGroups' => $profileNavGroups,
        ]);
    }

    /**
     * Regenerate the company embed / tracker API token (same token as Widget embed settings).
     */
    public function regenerateEmbedToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->company, 403);
        abort_if(! $user->hasAnyRole(['company_admin', 'team_member']), 403);

        $plain = $user->company->regenerateApiToken();
        $request->session()->flash('flowdesk_company_api_token_plain', $plain);

        return Redirect::route('profile.edit')
            ->with('status', __('New API token generated. Copy it on this page — it will not be shown again.'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->password !== null) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        } else {
            $request->validateWithBag('userDeletion', [
                'confirm_delete' => ['accepted'],
            ]);
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
