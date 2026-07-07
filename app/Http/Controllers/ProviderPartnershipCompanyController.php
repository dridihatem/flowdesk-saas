<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Services\ProviderPartnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderPartnershipCompanyController extends Controller
{
    public function show(Request $request, Provider $provider): View
    {
        $this->authorizeCompanyAdmin($request, $provider);

        $provider->loadMissing(['company', 'user']);
        $svc = app(ProviderPartnershipService::class);

        return view('providers.partnership-company', [
            'provider' => $provider,
            'contractHeader' => $svc->contractHeaderPlain($provider),
            'contractTerms' => $svc->resolvedTermsTextForProvider($provider),
            'contractTermsIsHtml' => $svc->termsBodyIsHtml($provider->company),
        ]);
    }

    public function contract(Request $request, Provider $provider): View
    {
        $this->authorizeCompanyAdmin($request, $provider);

        $provider->loadMissing(['company', 'user']);
        $svc = app(ProviderPartnershipService::class);

        return view('provider.partnership-contract', [
            'provider' => $provider,
            'contractHeader' => $svc->contractHeaderPlain($provider),
            'contractTerms' => $svc->resolvedTermsTextForProvider($provider),
            'contractTermsIsHtml' => $svc->termsBodyIsHtml($provider->company),
            'viewer' => 'company',
            'canSign' => false,
        ]);
    }

    public function signature(Request $request, Provider $provider): View
    {
        $this->authorizeCompanyAdmin($request, $provider);
        $provider->loadMissing(['company', 'user']);
        abort_if(! $provider->partnership_provider_signature_data, 404);

        return view('providers.partnership-signature', [
            'provider' => $provider,
        ]);
    }

    public function sign(Request $request, Provider $provider, ProviderPartnershipService $partnership): RedirectResponse
    {
        $this->authorizeCompanyAdmin($request, $provider);

        $request->validate([
            'accept' => ['accepted'],
        ]);

        $partnership->recordCompanySignature($provider, $request->user());

        return redirect()
            ->route('providers.index')
            ->with('status', __('Partnership signed. :name can now use the provider workspace.', ['name' => $provider->name]));
    }

    private function authorizeCompanyAdmin(Request $request, Provider $provider): void
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('company_admin'), 403);

        $company = $user->company;
        abort_if(! $company || (string) $provider->company_id !== (string) $company->id, 403);
    }
}
