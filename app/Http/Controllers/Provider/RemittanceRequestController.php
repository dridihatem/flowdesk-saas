<?php

namespace App\Http\Controllers\Provider;

use App\Enums\RemittanceMethod;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ResolvesProviderProfile;
use App\Models\ProviderRemittanceRequest;
use App\Services\ProviderCommissionBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RemittanceRequestController extends Controller
{
    use ResolvesProviderProfile;

    public function index(Request $request, ProviderCommissionBalanceService $balances): View
    {
        $provider = $this->providerOrAbort();
        $provider->loadMissing('company');

        $summary = $balances->summary($provider);
        $recentCommissions = $balances->recentCommissions($provider);

        $requests = ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('provider_id', $provider->id)
            ->latest()
            ->paginate(15);

        return view('provider.remittance-requests.index', compact(
            'provider',
            'summary',
            'recentCommissions',
            'requests',
        ));
    }

    public function store(Request $request, ProviderCommissionBalanceService $balances): RedirectResponse
    {
        $provider = $this->providerOrAbort();
        $summary = $balances->summary($provider);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(RemittanceMethod::class)],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $currency = $summary['currency'];
        $amountMinor = flowdesk_decimal_to_minor((string) $data['amount'], $currency) ?? 0;

        if ($amountMinor <= 0) {
            return back()->withErrors(['amount' => __('Enter a valid amount.')])->withInput();
        }

        if ($amountMinor > $summary['balance_due_minor']) {
            return back()->withErrors([
                'amount' => __('Amount exceeds your remaining commission balance.'),
            ])->withInput();
        }

        ProviderRemittanceRequest::query()->create([
            'company_id' => $provider->company_id,
            'provider_id' => $provider->id,
            'amount_minor' => $amountMinor,
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('provider.remittance-requests.index')
            ->with('status', __('provider_remittance_submitted'));
    }
}
