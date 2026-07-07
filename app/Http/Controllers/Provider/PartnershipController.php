<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\ProviderPartnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnershipController extends Controller
{
    private const SIGNATURE_MAX_BYTES = 524288;

    public function show(Request $request, ProviderPartnershipService $partnership): View|RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->with('company')
            ->firstOrFail();

        if ($provider->isPartnershipActive()) {
            return redirect()->route('provider.dashboard');
        }

        return view('provider.partnership', [
            'provider' => $provider,
            'contractHeader' => $partnership->contractHeaderPlain($provider),
            'contractTerms' => $partnership->resolvedTermsTextForProvider($provider),
            'contractTermsIsHtml' => $partnership->termsBodyIsHtml($provider->company),
        ]);
    }

    public function contract(Request $request, ProviderPartnershipService $partnership): View
    {
        $user = $request->user();
        abort_if(! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->with('company')
            ->firstOrFail();

        $canSign = $provider->needsProviderPartnershipSignature();

        return view('provider.partnership-contract', [
            'provider' => $provider,
            'contractHeader' => $partnership->contractHeaderPlain($provider),
            'contractTerms' => $partnership->resolvedTermsTextForProvider($provider),
            'contractTermsIsHtml' => $partnership->termsBodyIsHtml($provider->company),
            'canSign' => $canSign,
        ]);
    }

    public function sign(Request $request, ProviderPartnershipService $partnership): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $provider->needsProviderPartnershipSignature()) {
            return redirect()
                ->route('provider.partnership.show')
                ->with('status', __('No signature is required at this stage.'));
        }

        $validated = $request->validate([
            'accept' => ['accepted'],
            'signature_data' => ['required', 'string', 'max:700000', 'regex:/^data:image\/png;base64,/'],
        ]);

        $raw = $validated['signature_data'];
        $b64 = substr($raw, strlen('data:image/png;base64,'));
        $binary = base64_decode($b64, true);
        if ($binary === false || strlen($binary) > self::SIGNATURE_MAX_BYTES) {
            throw ValidationException::withMessages([
                'signature_data' => __('The signature image is invalid or too large.'),
            ]);
        }

        $partnership->recordProviderSignature($provider, $validated['signature_data']);

        return redirect()
            ->route('provider.partnership.show')
            ->with('status', __('You signed the partnership. The company will be notified to sign on their side.'));
    }
}
