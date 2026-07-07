<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesPortalClient;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteRequestController extends Controller
{
    use ResolvesPortalClient;

    public function index(Request $request): View
    {
        $client = $this->portalClient($request);

        $requests = Inquiry::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->where('source', 'client_portal')
            ->latest()
            ->paginate(15);

        return view('portal.quote-requests.index', compact('client', 'requests'));
    }

    public function create(Request $request): View
    {
        $client = $this->portalClient($request);

        return view('portal.quote-requests.create', compact('client'));
    }

    public function store(Request $request): RedirectResponse
    {
        $client = $this->portalClient($request);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
        ]);

        Inquiry::query()->withoutGlobalScopes()->create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'contact_name' => $client->name,
            'contact_email' => $client->email,
            'contact_phone' => $data['contact_phone'] ?? $client->phone,
            'source' => 'client_portal',
            'status' => InquiryStatus::New,
        ]);

        return redirect()
            ->route('portal.quote-requests.index')
            ->with('status', __('portal_quote_request_sent'));
    }

    public function show(Request $request, Inquiry $inquiry): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalQuoteRequest($client, $inquiry);

        return view('portal.quote-requests.show', compact('client', 'inquiry'));
    }
}
