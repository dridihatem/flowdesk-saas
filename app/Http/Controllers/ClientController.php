<?php

namespace App\Http\Controllers;

use App\Enums\ClientSource;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientCodeService;
use App\Services\ClientCredentialsMailService;
use App\Services\ClientFollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        });
    }

    protected function assertWorkspaceClient(Request $request, Client $client): void
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if((string) $client->company_id !== (string) $company->id, 404);
    }

    protected function clientCanMutate(Client $client): bool
    {
        return ! $client->invoices()->exists() && ! $client->projects()->exists();
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $q = $request->string('q')->trim()->toString();
        $query = Client::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->withCount(['invoices', 'projects'])
            ->latest();

        if ($q !== '') {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        $clients = $query->paginate(15)->withQueryString();

        return view('clients.index', compact('clients', 'q'));
    }

    public function show(Request $request, Client $client, ClientFollowUpService $followUp): View
    {
        $this->assertWorkspaceClient($request, $client);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $payload = $followUp->showPayload($client, $company);
        $sourceCase = $client->source ? ClientSource::tryFrom($client->source) : null;
        $statusCase = $client->status instanceof ClientStatus
            ? $client->status
            : ClientStatus::tryFrom((string) ($client->status ?? ClientStatus::Active->value)) ?? ClientStatus::Active;
        $mutable = $this->clientCanMutate($client);
        $activeTab = in_array($tab = (string) $request->query('tab', 'overview'), [
            'overview', 'notes', 'timeline', 'tasks', 'invoices', 'proposals', 'payments', 'meetings', 'reminders', 'feedback', 'messages',
        ], true) ? $tab : 'overview';
        $activeMeetingId = $request->query('meeting');

        return view('clients.show', compact(
            'client',
            'company',
            'payload',
            'sourceCase',
            'statusCase',
            'mutable',
            'activeTab',
            'activeMeetingId',
        ));
    }

    public function create(): View
    {
        $company = auth()->user()->company;
        abort_if(! $company, 403);

        return view('clients.create');
    }

    public function edit(Request $request, Client $client): View
    {
        $this->assertWorkspaceClient($request, $client);
        abort_if(! $this->clientCanMutate($client), 403, __('This client has invoices or projects and cannot be edited.'));

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->assertWorkspaceClient($request, $client);
        abort_if(! $this->clientCanMutate($client), 403, __('This client has invoices or projects and cannot be updated.'));

        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_with:portal_password', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', 'string', Rule::in(ClientSource::values())],
            'status' => ['nullable', 'string', Rule::in(ClientStatus::values())],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_country' => ['nullable', 'string', 'max:8'],
            'portal_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'portal_send_credentials' => ['nullable', 'boolean'],
        ]);

        $address = array_filter([
            'line1' => $data['address_line1'] ?? null,
            'city' => $data['address_city'] ?? null,
            'country' => $data['address_country'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $address = $address === [] ? null : $address;

        $client->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? ($client->status?->value ?? ClientStatus::Active->value),
            'address' => $address,
        ]);

        app(ClientCodeService::class)->assignIfMissing($client);

        $statusSuffix = $this->syncPortalAccount(
            $request,
            $client,
            $data['portal_password'] ?? null,
            (bool) ($data['portal_send_credentials'] ?? false),
        );

        return redirect()->route('clients.index')->with('status', __('Client updated.').$statusSuffix);
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->assertWorkspaceClient($request, $client);
        abort_if(! $this->clientCanMutate($client), 403, __('This client has invoices or projects and cannot be deleted.'));

        $client->delete();

        return redirect()->route('clients.index')->with('status', __('Client deleted.'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_with:portal_password', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', 'string', Rule::in(ClientSource::values())],
            'status' => ['nullable', 'string', Rule::in(ClientStatus::values())],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_country' => ['nullable', 'string', 'max:8'],
            'portal_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'portal_send_credentials' => ['nullable', 'boolean'],
        ]);

        $address = array_filter([
            'line1' => $data['address_line1'] ?? null,
            'city' => $data['address_city'] ?? null,
            'country' => $data['address_country'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $address = $address === [] ? null : $address;

        $client = Client::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? ClientStatus::Active->value,
            'address' => $address,
        ]);

        app(ClientCodeService::class)->assignIfMissing($client);

        $statusSuffix = $this->syncPortalAccount(
            $request,
            $client,
            $data['portal_password'] ?? null,
            (bool) ($data['portal_send_credentials'] ?? false),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'code' => $client->code,
                ],
            ]);
        }

        return redirect()->route('clients.index')->with('status', __('Client created.').$statusSuffix);
    }

    /**
     * Creates or updates the linked portal user when a password is provided,
     * and optionally emails the credentials to the client.
     *
     * @return string Suffix appended to the flash status message.
     */
    private function syncPortalAccount(Request $request, Client $client, ?string $password, bool $sendCredentials): string
    {
        if ($password === null || $password === '') {
            return '';
        }

        $email = (string) $client->email;
        $user = $client->user;

        if ($user) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        } else {
            $existing = User::query()->where('email', $email)->exists();
            if ($existing) {
                throw ValidationException::withMessages([
                    'portal_password' => __('client_portal_email_taken'),
                ]);
            }

            $user = DB::transaction(function () use ($client, $request, $email, $password): User {
                $user = User::query()->create([
                    'name' => $client->name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'company_id' => $client->company_id,
                    'locale' => $request->user()->locale,
                ]);
                $user->assignRole('client');
                $client->update(['user_id' => $user->id]);

                return $user;
            });
        }

        $suffix = ' '.__('client_portal_password_set');

        if ($sendCredentials) {
            try {
                app(ClientCredentialsMailService::class)->send($client, $request->user()->company, $password);
                $suffix = ' '.__('client_portal_credentials_sent');
            } catch (\Throwable $e) {
                report($e);
                $suffix = ' '.__('client_portal_credentials_send_failed');
            }
        }

        return $suffix;
    }
}
