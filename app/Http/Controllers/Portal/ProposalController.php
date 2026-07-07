<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesPortalClient;
use App\Models\Proposal;
use App\Services\ProposalPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalController extends Controller
{
    use ResolvesPortalClient;

    public function index(Request $request): View
    {
        $client = $this->portalClient($request);

        $proposals = $client->proposals()
            ->whereIn('status', [
                ProposalStatus::Sent->value,
                ProposalStatus::Accepted->value,
                ProposalStatus::Rejected->value,
                ProposalStatus::Expired->value,
            ])
            ->with(['project'])
            ->latest()
            ->paginate(15);

        return view('portal.proposals.index', compact('client', 'proposals'));
    }

    public function show(Request $request, Proposal $proposal): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProposal($client, $proposal);
        abort_if($proposal->status === ProposalStatus::Draft, 404);

        $proposal->load(['items', 'project', 'company']);

        return view('portal.proposals.show', compact('client', 'proposal'));
    }

    public function pdf(Request $request, Proposal $proposal, ProposalPdfService $pdfs): StreamedResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProposal($client, $proposal);
        abort_if($proposal->status === ProposalStatus::Draft, 404);

        return $pdfs->stream($proposal->loadMissing(['client', 'items', 'company']));
    }

    public function accept(Request $request, Proposal $proposal): RedirectResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProposal($client, $proposal);

        if ($proposal->status === ProposalStatus::Accepted) {
            return redirect()->route('portal.proposals.show', $proposal)->with('status', __('Quote is already accepted.'));
        }

        if ($proposal->status === ProposalStatus::Rejected) {
            return redirect()->route('portal.proposals.show', $proposal)->withErrors(['accept' => __('Cannot accept a rejected quote.')]);
        }

        if ($proposal->status === ProposalStatus::Draft) {
            abort(404);
        }

        $proposal->update(['status' => ProposalStatus::Accepted]);

        return redirect()->route('portal.proposals.show', $proposal)->with('status', __('portal_quote_accepted'));
    }
}
