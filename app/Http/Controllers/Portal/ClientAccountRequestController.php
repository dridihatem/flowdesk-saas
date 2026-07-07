<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientAccountRequest;
use App\Models\User;
use App\Services\ChatThreadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientAccountRequestController extends Controller
{
    public function __construct(
        private readonly ChatThreadService $chatThreads,
    ) {}

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        $invitations = ClientAccountRequest::query()->withoutGlobalScopes()
            ->where('requester_client_id', $client->id)
            ->with(['createdClient.user'])
            ->latest()
            ->get();

        $teamThread = $this->chatThreads->clientThreadFor($client);
        $chatParticipantIds = $teamThread->participants()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        return view('portal.client-account-request', compact('client', 'invitations', 'teamThread', 'chatParticipantIds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $requesterClient = $user->clientProfile;
        abort_if(! $requesterClient, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'add_to_chat' => ['sometimes', 'boolean'],
        ]);

        if (User::query()->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => __('This email is already registered.')])->withInput();
        }

        ClientAccountRequest::query()->create([
            'company_id' => $requesterClient->company_id,
            'requester_client_id' => $requesterClient->id,
            'requester_user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'add_to_chat' => $request->boolean('add_to_chat'),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('portal.client-account-requests.create')
            ->with('status', __('Your request was sent. The company will review and activate the account if approved.'));
    }

    public function addToChat(Request $request, ClientAccountRequest $clientAccountRequest): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);
        abort_if((string) $clientAccountRequest->requester_client_id !== (string) $client->id, 403);
        abort_if($clientAccountRequest->status !== 'approved', 403);

        $colleagueUser = $clientAccountRequest->createdClient?->user;
        abort_if(! $colleagueUser, 404);

        $thread = $this->chatThreads->clientThreadFor($client);
        $this->chatThreads->addParticipant($thread, (int) $colleagueUser->id);

        return back()->with('status', __('Colleague added to team chat.'));
    }
}
