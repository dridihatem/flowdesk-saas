<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientAccountRequest;
use App\Models\User;
use App\Services\ChatThreadService;
use App\Services\ClientCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientAccountRequestReviewController extends Controller
{
    public function __construct(
        private readonly ChatThreadService $chatThreads,
    ) {
        $this->middleware(function (Request $request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);
            abort_if(! $request->user()->can('workspace.manage_clients'), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $pending = ClientAccountRequest::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->with(['requesterClient', 'requesterUser'])
            ->latest()
            ->get();

        $recent = ClientAccountRequest::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', '!=', 'pending')
            ->with(['requesterClient', 'reviewedBy', 'createdClient'])
            ->latest()
            ->take(20)
            ->get();

        return view('clients.account-requests', compact('pending', 'recent'));
    }

    public function approve(Request $request, ClientAccountRequest $clientAccountRequest): RedirectResponse
    {
        $this->assertWorkspaceRequest($request, $clientAccountRequest);
        abort_if(! $clientAccountRequest->isPending(), 403);

        if (User::query()->where('email', $clientAccountRequest->email)->exists()) {
            return redirect()
                ->route('clients.account-requests.index')
                ->withErrors(['approve' => __('This email is already registered.')]);
        }

        if (Client::query()->withoutGlobalScopes()
            ->where('company_id', $clientAccountRequest->company_id)
            ->where('email', $clientAccountRequest->email)
            ->exists()) {
            return redirect()
                ->route('clients.account-requests.index')
                ->withErrors(['approve' => __('A client with this email already exists in your workspace.')]);
        }

        DB::transaction(function () use ($clientAccountRequest, $request): void {
            $user = User::query()->create([
                'name' => $clientAccountRequest->name,
                'email' => $clientAccountRequest->email,
                'password' => Hash::make(Str::password(32)),
                'company_id' => $clientAccountRequest->company_id,
                'locale' => $request->user()->locale,
            ]);
            $user->assignRole('client');

            $client = Client::query()->withoutGlobalScopes()->create([
                'company_id' => $clientAccountRequest->company_id,
                'name' => $clientAccountRequest->name,
                'email' => $clientAccountRequest->email,
                'phone' => $clientAccountRequest->phone,
                'user_id' => $user->id,
            ]);
            app(ClientCodeService::class)->assignIfMissing($client);

            $clientAccountRequest->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $request->user()->id,
                'created_client_id' => $client->id,
            ]);

            if ($clientAccountRequest->add_to_chat) {
                $requesterClient = Client::query()->withoutGlobalScopes()->find($clientAccountRequest->requester_client_id);
                if ($requesterClient) {
                    $thread = $this->chatThreads->clientThreadFor($requesterClient);
                    $this->chatThreads->addParticipant($thread, (int) $user->id);
                }
            }

            Password::broker('users')->sendResetLink(['email' => $user->email]);
        });

        return redirect()
            ->route('clients.account-requests.index')
            ->with('status', __('Account approved. The new client will receive an email to set their password.'));
    }

    public function reject(Request $request, ClientAccountRequest $clientAccountRequest): RedirectResponse
    {
        $this->assertWorkspaceRequest($request, $clientAccountRequest);
        abort_if(! $clientAccountRequest->isPending(), 403);

        $clientAccountRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('clients.account-requests.index')
            ->with('status', __('Request rejected.'));
    }

    private function assertWorkspaceRequest(Request $request, ClientAccountRequest $row): void
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if((string) $row->company_id !== (string) $company->id, 404);
    }
}
