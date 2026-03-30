<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CompanyNamingService;
use App\Services\TenantStorageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OAuthCompanyController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('oauth_pending')) {
            return redirect()->route('register');
        }

        $pending = $request->session()->get('oauth_pending');

        return view('auth.oauth-company', [
            'pending' => $pending,
        ]);
    }

    public function store(Request $request, CompanyNamingService $naming, TenantStorageService $tenantStorage): RedirectResponse
    {
        if (! $request->session()->has('oauth_pending')) {
            return redirect()->route('register');
        }

        $pending = $request->session()->get('oauth_pending');

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $defaultCurrency = $this->currencyForCountry($validated['country'] ?? null);

        [$company, $user] = DB::transaction(function () use ($validated, $pending, $naming, $tenantStorage, $defaultCurrency) {
            $subdomain = $naming->uniqueSubdomain($validated['company_name']);
            $slug = $naming->uniqueSlug($validated['company_name']);

            $company = Company::query()->create([
                'name' => $validated['company_name'],
                'subdomain' => $subdomain,
                'slug' => $slug,
                'country' => $validated['country'] ?? null,
                'default_currency' => $defaultCurrency,
            ]);

            $tenantStorage->bootstrap($company);

            $company->regenerateApiToken();

            $user = User::query()->create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => null,
                'company_id' => $company->id,
                'locale' => app()->getLocale(),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('company_admin');

            SocialAccount::query()->create([
                'user_id' => $user->id,
                'provider' => $pending['driver'],
                'provider_user_id' => $pending['provider_user_id'],
                'email' => $pending['email'],
                'avatar_url' => $pending['avatar_url'] ?? null,
            ]);

            return [$company, $user];
        });

        $request->session()->forget('oauth_pending');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))->with('registration', [
            'company_api_token' => null,
            'sanctum_token' => null,
            'subdomain' => $company->subdomain,
            'tenant_url' => flowdesk_tenant_url($company, '/dashboard'),
            'oauth' => true,
        ]);
    }

    private function currencyForCountry(?string $country): string
    {
        if ($country === null) {
            return 'USD';
        }

        $map = config('flowdesk.country_currency', []);

        return $map[strtoupper((string) $country)] ?? 'USD';
    }
}
