<?php

namespace App\Http\Controllers;

use App\Enums\ProviderRemittanceStatus;
use App\Models\Provider;
use App\Models\ProviderRemittanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProviderRemittanceReviewController extends Controller
{
    public function approve(Request $request, Provider $provider, ProviderRemittanceRequest $remittanceRequest): RedirectResponse
    {
        $this->authorizeReview($request, $provider, $remittanceRequest);

        $remittanceRequest->update([
            'status' => ProviderRemittanceStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', __('provider_remittance_approved'));
    }

    public function reject(Request $request, Provider $provider, ProviderRemittanceRequest $remittanceRequest): RedirectResponse
    {
        $this->authorizeReview($request, $provider, $remittanceRequest);

        $remittanceRequest->update([
            'status' => ProviderRemittanceStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', __('provider_remittance_rejected'));
    }

    private function authorizeReview(Request $request, Provider $provider, ProviderRemittanceRequest $remittanceRequest): void
    {
        abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);
        abort_if((string) $provider->company_id !== (string) $request->user()->company_id, 403);
        abort_if((string) $remittanceRequest->provider_id !== (string) $provider->id, 404);
        abort_if(! $remittanceRequest->isPending(), 403);
    }
}
