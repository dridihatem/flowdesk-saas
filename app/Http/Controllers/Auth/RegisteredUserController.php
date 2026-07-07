<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyNamingService;
use App\Services\LocaleDetectionService;
use App\Services\MathCaptchaService;
use App\Services\SubscriptionBootstrapService;
use App\Services\TenantStorageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const CAPTCHA_CONTEXT = 'auth-register';

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

    public function create(MathCaptchaService $mathCaptcha): View
    {
        return view('auth.register', [
            'countries' => config('flowdesk_countries', []),
            'dialCodes' => config('flowdesk_country_dial_codes', []),
            'captcha' => $mathCaptcha->generate(self::CAPTCHA_CONTEXT),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, MathCaptchaService $mathCaptcha): RedirectResponse
    {
        $captchaError = $mathCaptcha->validate($request, self::CAPTCHA_CONTEXT);
        if ($captchaError !== null) {
            return back()
                ->withErrors(['_captcha_answer' => $captchaError])
                ->withInput($request->except('password', 'password_confirmation', '_captcha_token', '_captcha_answer'));
        }

        if ($request->input('country') === '') {
            $request->merge(['country' => null]);
        }
        if ($request->input('phone_country_iso') === '') {
            $request->merge(['phone_country_iso' => null]);
        }

        $request->validate([
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($request->filled('phone_national_number') && ! $request->filled('phone_country_iso') && ! $request->filled('country')) {
            throw ValidationException::withMessages([
                'phone_country_iso' => __('Choose a country code (or set your company country) when entering a phone number.'),
            ]);
        }

        $naming = app(CompanyNamingService::class);
        $tenantStorage = app(TenantStorageService::class);

        [$company, $user, $companyApiToken, $sanctumToken] = DB::transaction(function () use ($request, $naming, $tenantStorage) {
            $subdomain = $naming->uniqueSubdomain($request->company_name);
            $slug = $naming->uniqueSlug($request->company_name);

            $country = $request->input('country') ? strtoupper((string) $request->input('country')) : null;
            $defaultCurrency = $request->filled('default_currency')
                ? strtoupper((string) $request->input('default_currency'))
                : ($country
                    ? (config('flowdesk.country_currency', [])[$country] ?? 'USD')
                    : 'USD');

            $defaultLocale = app(LocaleDetectionService::class)
                ->defaultLocaleForRegistration($request, $country);

            $company = Company::query()->create([
                'name' => $request->company_name,
                'contact_email' => $request->input('contact_email'),
                'phone' => flowdesk_compose_international_phone(
                    $request->input('phone_country_iso'),
                    $request->input('country'),
                    $request->input('phone_national_number')
                ),
                'website' => $this->normalizeWebsite($request->input('website')),
                'tax_id' => $request->input('tax_id'),
                'address_line1' => $request->input('address_line1'),
                'city' => $request->input('city'),
                'postal_code' => $request->input('postal_code'),
                'industry' => $request->input('industry'),
                'subdomain' => $subdomain,
                'slug' => $slug,
                'country' => $country,
                'default_currency' => $defaultCurrency,
                'default_locale' => $defaultLocale,
            ]);

            $tenantStorage->bootstrap($company);

            flowdesk_apply_company_billing_vat($company, $request->input('vat_percent'));

            app(SubscriptionBootstrapService::class)->ensureDefaultSubscription($company);

            $companyApiToken = $company->regenerateApiToken();

            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
                'locale' => $defaultLocale,
            ]);

            $user->assignRole('company_admin');

            $sanctumToken = $user->createToken('primary')->plainTextToken;

            return [$company, $user, $companyApiToken, $sanctumToken];
        });

        event(new Registered($user));

        Auth::login($user);

        session([
            'locale' => $company->default_locale,
            'locale_auto_pinned' => true,
        ]);

        return redirect(route('dashboard', absolute: false))->with('registration', [
            'company_api_token' => $companyApiToken,
            'sanctum_token' => $sanctumToken,
            'subdomain' => $company->subdomain,
            'tenant_url' => flowdesk_tenant_url($company, '/dashboard'),
        ]);
    }
}
