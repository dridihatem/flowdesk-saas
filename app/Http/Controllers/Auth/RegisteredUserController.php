<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyNamingService;
use App\Services\TenantStorageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $naming = app(CompanyNamingService::class);
        $tenantStorage = app(TenantStorageService::class);

        [$company, $user, $companyApiToken, $sanctumToken] = DB::transaction(function () use ($request, $naming, $tenantStorage) {
            $subdomain = $naming->uniqueSubdomain($request->company_name);
            $slug = $naming->uniqueSlug($request->company_name);

            $company = Company::query()->create([
                'name' => $request->company_name,
                'subdomain' => $subdomain,
                'slug' => $slug,
            ]);

            $tenantStorage->bootstrap($company);

            $companyApiToken = $company->regenerateApiToken();

            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
            ]);

            $user->assignRole('company_admin');

            $sanctumToken = $user->createToken('primary')->plainTextToken;

            return [$company, $user, $companyApiToken, $sanctumToken];
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false))->with('registration', [
            'company_api_token' => $companyApiToken,
            'sanctum_token' => $sanctumToken,
            'subdomain' => $company->subdomain,
            'tenant_url' => flowdesk_tenant_url($company, '/dashboard'),
        ]);
    }
}
