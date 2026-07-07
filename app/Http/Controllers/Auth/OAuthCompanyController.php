<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CompanyNamingService;
use App\Services\LocaleDetectionService;
use App\Services\SubscriptionBootstrapService;
use App\Services\TenantStorageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OAuthCompanyController extends Controller
{
    private function normalizeWebsite(?string $website): ?string
    {
        if ($website === null || trim($website) === '') {
            return null;
        }

        $w = trim($website);
        if (! preg_match('#^https?://#i', $w)) {
            $w = 'https://'.$w;
        }

        return substr($w, 0, 255);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('oauth_pending')) {
            return redirect()->route('register');
        }

        $pending = $request->session()->get('oauth_pending');

        return view('auth.oauth-company', [
            'pending' => $pending,
            'countries' => config('flowdesk_countries', []),
            'dialCodes' => config('flowdesk_country_dial_codes', []),
        ]);
    }

    public function store(Request $request, CompanyNamingService $naming, TenantStorageService $tenantStorage): RedirectResponse
    {
        if (! $request->session()->has('oauth_pending')) {
            return redirect()->route('register');
        }

        $pending = $request->session()->get('oauth_pending');

        if ($request->input('country') === '') {
            $request->merge(['country' => null]);
        }
        if ($request->input('phone_country_iso') === '') {
            $request->merge(['phone_country_iso' => null]);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', Rule::in(array_keys(config('flowdesk_countries', [])))],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone_country_iso' => ['nullable', 'string', 'size:2', Rule::in(array_keys(config('flowdesk_countries', [])))],
            'phone_national_number' => ['nullable', 'string', 'regex:/^[0-9]{1,15}$/'],
            'website' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'industry' => ['nullable', 'string', 'max:120'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (! empty($validated['phone_national_number'] ?? '') && empty($validated['phone_country_iso'] ?? null) && empty($validated['country'] ?? null)) {
            return redirect()->back()->withInput()->withErrors([
                'phone_country_iso' => __('Choose a country code (or set your company country) when entering a phone number.'),
            ]);
        }

        $country = isset($validated['country']) ? strtoupper((string) $validated['country']) : null;
        $defaultCurrency = ! empty($validated['default_currency'])
            ? strtoupper((string) $validated['default_currency'])
            : $this->currencyForCountry($country);

        [$company, $user] = DB::transaction(function () use ($validated, $pending, $naming, $tenantStorage, $defaultCurrency, $country, $request) {
            $subdomain = $naming->uniqueSubdomain($validated['company_name']);
            $slug = $naming->uniqueSlug($validated['company_name']);

            $defaultLocale = app(LocaleDetectionService::class)
                ->defaultLocaleForRegistration($request, $country);

            $company = Company::query()->create([
                'name' => $validated['company_name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'phone' => flowdesk_compose_international_phone(
                    $validated['phone_country_iso'] ?? null,
                    $validated['country'] ?? null,
                    $validated['phone_national_number'] ?? null
                ),
                'website' => $this->normalizeWebsite($validated['website'] ?? null),
                'tax_id' => $validated['tax_id'] ?? null,
                'address_line1' => $validated['address_line1'] ?? null,
                'city' => $validated['city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'industry' => $validated['industry'] ?? null,
                'subdomain' => $subdomain,
                'slug' => $slug,
                'country' => $country,
                'default_currency' => $defaultCurrency,
                'default_locale' => $defaultLocale,
            ]);

            $tenantStorage->bootstrap($company);

            flowdesk_apply_company_billing_vat($company, $validated['vat_percent'] ?? null);

            app(SubscriptionBootstrapService::class)->ensureDefaultSubscription($company);

            $company->regenerateApiToken();

            $user = User::query()->create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => null,
                'company_id' => $company->id,
                'locale' => $defaultLocale,
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
