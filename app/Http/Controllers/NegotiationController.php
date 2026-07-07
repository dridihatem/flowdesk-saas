<?php

namespace App\Http\Controllers;

use App\Enums\NegotiationStatus;
use App\Enums\ProposalStatus;
use App\Models\Negotiation;
use App\Models\Proposal;
use App\Services\ProviderCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NegotiationController extends Controller
{
    public function store(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposal($proposal);
        abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

        $proposal->loadMissing('company');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($proposal->company?->default_currency, $proposal->currency)],
            'notes' => ['nullable', 'string'],
        ]);

        Negotiation::query()->withoutGlobalScopes()->create([
            'company_id' => $proposal->company_id,
            'proposal_id' => $proposal->id,
            'status' => NegotiationStatus::CounterOffer,
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('proposals.show', $proposal)->with('status', __('Negotiation updated.'));
    }

    public function accept(Request $request, Negotiation $negotiation): RedirectResponse
    {
        $this->authorizeNegotiation($negotiation);
        abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

        $proposal = $negotiation->proposal;
        $proposal->load('project.provider');

        $commission = null;
        $provider = $proposal->project?->provider;
        if ($provider !== null && $negotiation->amount !== null) {
            $rate = app(ProviderCommissionService::class)->effectiveRateForProject($provider, $proposal->project);
            if ($rate !== null) {
                $commission = (int) round($negotiation->amount * $rate);
            }
        }

        $negotiation->update([
            'status' => NegotiationStatus::Accepted,
            'commission_amount_minor' => $commission,
        ]);

        $proposal->update(['status' => ProposalStatus::Accepted]);

        return redirect()->route('proposals.show', $proposal)->with('status', __('Deal accepted.'));
    }

    public function reject(Request $request, Negotiation $negotiation): RedirectResponse
    {
        $this->authorizeNegotiation($negotiation);
        abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

        $proposal = $negotiation->proposal;

        $negotiation->update([
            'status' => NegotiationStatus::Rejected,
        ]);

        $proposal->update(['status' => ProposalStatus::Rejected]);

        return redirect()->route('proposals.show', $proposal)->with('status', __('Deal rejected.'));
    }

    private function authorizeProposal(Proposal $proposal): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $proposal->company_id !== (string) $company->id, 403);
    }

    private function authorizeNegotiation(Negotiation $negotiation): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $negotiation->company_id !== (string) $company->id, 403);
    }
}
