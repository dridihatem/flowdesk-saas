<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderRecruitmentRequest;
use App\Models\Provider;
use App\Services\CompanyNamingService;
use App\Services\ProviderPartnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProviderRecruitmentSettingsController extends Controller
{
    public function edit(Request $request, ProviderPartnershipService $partnership): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $partnershipProviders = Provider::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('user')
            ->latest()
            ->get();

        return view('settings.provider-recruitment', [
            'company' => $company,
            'partnershipProviders' => $partnershipProviders,
            'partnershipTemplateHints' => $partnership->partnershipTemplateEditorHints(),
        ]);
    }

    public function update(UpdateProviderRecruitmentRequest $request, CompanyNamingService $naming): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $validated = $request->validated();
        $enabled = $request->boolean('provider_recruitment_enabled');
        $slug = trim((string) ($validated['provider_recruitment_slug'] ?? ''));
        if ($slug !== '') {
            $slug = Str::slug($slug);
            $slug = Str::limit($slug, 64, '');
            $slug = trim($slug, '-') ?: $slug;
        }

        if ($request->boolean('regenerate_slug')) {
            $slug = $naming->uniqueProviderRecruitmentSlug($company->name);
        }

        if ($enabled && $slug === '') {
            $slug = $company->provider_recruitment_slug ?? $naming->uniqueProviderRecruitmentSlug($company->name);
        }

        if (! $enabled) {
            $slug = $company->provider_recruitment_slug;
        }

        $company->update([
            'provider_recruitment_enabled' => $enabled,
            'provider_recruitment_slug' => $enabled ? $slug : $company->provider_recruitment_slug,
            'provider_partnership_terms' => $validated['provider_partnership_terms'] ?? null,
        ]);

        return redirect()
            ->route('settings.provider-recruitment')
            ->with('status', __('Provider recruitment settings saved.'));
    }

    /**
     * Default partnership body text for a locale (for the rich editor), as safe HTML paragraphs.
     */
    public function sampleTerms(Request $request, string $locale): JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $allowed = ['en', 'fr', 'es', 'ar'];
        abort_unless(in_array($locale, $allowed, true), 404);

        $text = (string) __('provider_partnership_default_terms', ['company' => $company->name], $locale);
        $paragraphs = preg_split('/\r\n|\r|\n/', $text) ?: [$text];
        $html = '';
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            $html .= '<p>'.e($p).'</p>';
        }

        return response()->json(['html' => $html !== '' ? $html : '<p></p>']);
    }
}
