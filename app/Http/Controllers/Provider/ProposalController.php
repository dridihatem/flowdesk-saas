<?php

namespace App\Http\Controllers\Provider;

use App\Enums\NegotiationStatus;
use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\Negotiation;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function create(Request $request, Project $project): View
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);
        $project->loadMissing('company');
        $currencyOptions = flowdesk_currency_select_options($project->company?->default_currency);

        return view('provider.proposals.create', compact('project', 'provider', 'currencyOptions'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);
        $project->loadMissing('company');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($project->company?->default_currency)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $currency = strtoupper($data['currency']);

        $proposal = Proposal::query()->withoutGlobalScopes()->create([
            'company_id' => $project->company_id,
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'provider_id' => $provider->id,
            'name' => $data['name'],
            'status' => ProposalStatus::Sent,
            'amount' => $data['amount'],
            'currency' => $currency,
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        Negotiation::query()->withoutGlobalScopes()->create([
            'company_id' => $project->company_id,
            'proposal_id' => $proposal->id,
            'status' => NegotiationStatus::Submitted,
            'amount' => $data['amount'],
            'currency' => $currency,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('proposals.show', $proposal)->with('status', __('Estimate sent.'));
    }

    private function providerOrAbort(): Provider
    {
        $user = auth()->user();
        abort_if(! $user || ! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        abort_if(! $provider, 403);

        return $provider;
    }

    private function authorizeProject(Project $project, Provider $provider): void
    {
        abort_if((string) $project->company_id !== (string) $provider->company_id, 403);
        abort_if((string) $project->provider_id !== (string) $provider->id, 403);
    }
}
