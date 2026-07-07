<?php

namespace App\Http\Controllers;

use App\Enums\ProviderPartnershipStatus;
use App\Models\Company;
use App\Models\Provider;
use App\Models\User;
use App\Services\ProviderPartnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PartnerRegistrationController extends Controller
{
    public function show(string $slug): View
    {
        $company = Company::query()
            ->where('provider_recruitment_slug', $slug)
            ->where('provider_recruitment_enabled', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        return view('auth.partner-register', [
            'company' => $company,
            'slug' => $slug,
        ]);
    }

    public function store(Request $request, string $slug, ProviderPartnershipService $partnership): RedirectResponse
    {
        $company = Company::query()
            ->where('provider_recruitment_slug', $slug)
            ->where('provider_recruitment_enabled', true)
            ->where('is_enabled', true)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ]);

        $user = null;
        $provider = null;

        DB::transaction(function () use (&$user, &$provider, $company, $data): void {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $company->id,
                'locale' => app()->getLocale(),
            ]);
            $user->markEmailAsVerified();
            $user->syncRoles(['business_provider']);

            $provider = Provider::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'commission_rate' => null,
                'partnership_status' => ProviderPartnershipStatus::PendingProvider,
            ]);
        });

        Auth::login($user);

        $partnership->sendInviteMail($provider);

        return redirect()
            ->route('provider.dashboard')
            ->with('status', __('Check your email for a copy of the partnership. Open the contract from your dashboard to sign and continue.'));
    }
}
