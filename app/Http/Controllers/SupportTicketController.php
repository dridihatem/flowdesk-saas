<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketStatus;
use App\Models\Client;
use App\Models\Provider;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = SupportTicket::query()->with(['user', 'client', 'provider'])->latest();

        if ($user->hasAnyRole(['company_admin', 'team_member'])) {
            $tickets = $query->paginate(20);
        } else {
            $tickets = $query->where('user_id', $user->id)->paginate(20);
        }

        return view('tickets.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['company_admin', 'team_member']);
        $clients = collect();
        $providers = collect();

        if ($isStaff && $user->company_id) {
            $clients = Client::query()
                ->withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->orderBy('name')
                ->get(['id', 'name']);
            $providers = Provider::query()
                ->withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('tickets.create', compact('isStaff', 'clients', 'providers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['company_admin', 'team_member']);
        $companyId = $user->company_id;

        if ($isStaff) {
            $data = $request->validate([
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string', 'max:20000'],
                'related_type' => ['required', 'string', Rule::in(['none', 'client', 'provider'])],
                'client_id' => ['nullable', 'string'],
                'provider_id' => ['nullable', 'string'],
            ]);

            $clientId = null;
            $providerId = null;

            if ($data['related_type'] === 'client') {
                $request->validate([
                    'client_id' => [
                        'required',
                        'string',
                        Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                    ],
                ]);
                $clientId = $data['client_id'];
            } elseif ($data['related_type'] === 'provider') {
                $request->validate([
                    'provider_id' => [
                        'required',
                        'string',
                        Rule::exists('providers', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                    ],
                ]);
                $providerId = $data['provider_id'];
            }
        } else {
            $data = $request->validate([
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string', 'max:20000'],
            ]);

            $clientId = null;
            $providerId = null;

            if ($user->hasRole('client')) {
                $client = Client::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->first();
                $clientId = $client?->id;
            } elseif ($user->hasRole('business_provider')) {
                $provider = Provider::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->first();
                $providerId = $provider?->id;
            }
        }

        SupportTicket::query()->create([
            'user_id' => $user->id,
            'title' => $data['subject'],
            'description' => $data['message'],
            'status' => SupportTicketStatus::Open,
            'client_id' => $clientId ?? null,
            'provider_id' => $providerId ?? null,
        ]);

        return redirect()->route('tickets.index')->with('status', __('Ticket created.'));
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorizeTicket($ticket);

        $ticket->load(['user', 'client', 'provider']);

        return view('tickets.show', compact('ticket'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);
        abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::enum(SupportTicketStatus::class)],
        ]);

        $ticket->update(['status' => SupportTicketStatus::from($data['status'])]);

        return back()->with('status', __('Ticket updated.'));
    }

    private function authorizeTicket(SupportTicket $ticket): void
    {
        $user = auth()->user();
        abort_if(! $user, 403);
        abort_if((string) $ticket->company_id !== (string) $user->company_id, 403);

        if ($user->hasAnyRole(['company_admin', 'team_member'])) {
            return;
        }

        abort_if((string) $ticket->user_id !== (string) $user->id, 403);
    }
}
