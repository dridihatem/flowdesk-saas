<?php

namespace App\Http\Controllers;

use App\Enums\ProviderRemittanceStatus;
use App\Models\Provider;
use App\Models\ProviderRemittanceRequest;
use App\Models\User;
use App\Services\ProviderCommissionBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $q = $request->string('q')->trim()->toString();
        $query = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->withCount('projects')
            ->latest();

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('website', 'like', '%'.$q.'%')
                    ->orWhere('job_title', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        $providers = $query->paginate(15)->withQueryString();

        return view('providers.index', compact('providers', 'q'));
    }

    public function create(): View
    {
        $company = auth()->user()->company;
        abort_if(! $company, 403);
        $users = User::query()->where('company_id', $company->id)->workspaceStaff()->orderBy('name')->get();

        return view('providers.create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        if ($request->input('user_id') === '' || $request->input('user_id') === null) {
            $request->merge(['user_id' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'commission_rate' => ['nullable', 'numeric', 'between:0,100'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $linkedUserId = $this->validatedLinkedStaffUserId($data['user_id'] ?? null, $company->id);

        Provider::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'description' => $data['description'] ?? null,
            'commission_rate' => $this->normalizedCommissionRate($data['commission_rate'] ?? null),
            'commission_tiers' => null,
            'user_id' => $linkedUserId,
        ]);

        return redirect()->route('providers.index')->with('status', __('Provider created.'));
    }

    public function edit(Provider $provider): View
    {
        $this->authorizeProvider($provider);
        $users = User::query()->where('company_id', $provider->company_id)->workspaceStaff()->orderBy('name')->get();

        $pendingRemittances = ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('provider_id', $provider->id)
            ->where('status', ProviderRemittanceStatus::Pending)
            ->latest()
            ->get();

        $provider->loadMissing('company');
        $commissionSummary = app(ProviderCommissionBalanceService::class)->summary($provider);
        $commissionsByCompletedProject = app(ProviderCommissionBalanceService::class)
            ->commissionsByCompletedProject($provider);

        return view('providers.edit', [
            'provider' => $provider,
            'users' => $users,
            'pendingRemittances' => $pendingRemittances,
            'commissionSummary' => $commissionSummary,
            'commissionsByCompletedProject' => $commissionsByCompletedProject,
        ]);
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $this->authorizeProvider($provider);

        if ($request->input('user_id') === '' || $request->input('user_id') === null) {
            $request->merge(['user_id' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'commission_rate' => ['nullable', 'numeric', 'between:0,100'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $linkedUserId = $this->validatedLinkedStaffUserId($data['user_id'] ?? null, $provider->company_id);

        $provider->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'description' => $data['description'] ?? null,
            'commission_rate' => $this->normalizedCommissionRate($data['commission_rate'] ?? null),
            'commission_tiers' => null,
            'user_id' => $linkedUserId,
        ]);

        return redirect()->route('providers.index')->with('status', __('Provider updated.'));
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        $this->authorizeProvider($provider);
        $provider->delete();

        return redirect()->route('providers.index')->with('status', __('Provider removed.'));
    }

    private function authorizeProvider(Provider $provider): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $provider->company_id !== (string) $company->id, 403);
    }

    private function validatedLinkedStaffUserId(?int $userId, string $companyId): ?int
    {
        if ($userId === null || $userId === 0) {
            return null;
        }

        $u = User::query()
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->workspaceStaff()
            ->first();
        abort_if(! $u, 422);

        return $u->id;
    }

    private function normalizedCommissionRate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value / 100, 6);
    }
}
