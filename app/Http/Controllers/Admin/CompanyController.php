<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanyNamingService;
use App\Services\SubscriptionBootstrapService;
use App\Services\TenantStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->with('plan')
            ->withCount('users')
            ->latest()
            ->paginate(20);

        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        $plans = Plan::query()->orderBy('name')->get();
        $locales = config('flowdesk.locales', ['en']);
        $currencies = config('flowdesk.supported_currencies', ['USD']);
        $currencyLabels = config('flowdesk.currency_labels', []);

        return view('admin.companies.create', compact('plans', 'locales', 'currencies', 'currencyLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'default_locale' => ['required', 'string', Rule::in(config('flowdesk.locales', ['en']))],
            'default_currency' => ['required', 'string', Rule::in(config('flowdesk.supported_currencies', ['USD']))],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')],
            'plan_locked' => ['sometimes', 'boolean'],
        ]);

        $naming = app(CompanyNamingService::class);
        $tenantStorage = app(TenantStorageService::class);
        $bootstrap = app(SubscriptionBootstrapService::class);

        [$company, $user] = DB::transaction(function () use ($data, $naming, $tenantStorage, $bootstrap) {
            $slug = $naming->uniqueSlug($data['company_name']);
            $subdomain = $slug;

            $company = Company::query()->create([
                'name' => $data['company_name'],
                'contact_email' => $data['contact_email'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'subdomain' => $subdomain,
                'slug' => $slug,
                'default_locale' => $data['default_locale'],
                'default_currency' => $data['default_currency'],
            ]);

            $tenantStorage->bootstrap($company);

            $bootstrap->ensureDefaultSubscription($company);

            $company->forceFill([
                'plan_id' => $data['plan_id'] ?? $company->plan_id,
                'plan_locked' => (bool) ($data['plan_locked'] ?? false),
            ])->saveQuietly();

            $user = User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'company_id' => $company->id,
                'locale' => $company->default_locale,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('company_admin');

            return [$company, $user];
        });

        $tenantUrl = flowdesk_tenant_url($company, '/dashboard');
        $lines = [
            'Your Flowqil workspace is ready.',
            '',
            'Workspace: '.$company->name,
            'URL: '.$tenantUrl,
            '',
            'Login email: '.$user->email,
            'Temporary password: '.$data['admin_password'],
            '',
            'Please sign in and change your password.',
        ];

        try {
            Mail::raw(implode("\n", $lines), function ($m) use ($user, $company): void {
                $m->to($user->email)->subject('Workspace created: '.$company->name);
            });
        } catch (\Throwable $e) {
            // Email failures should not block company creation.
        }

        return redirect()->route('admin.companies.show', $company)->with('status', __('Company created.'));
    }

    public function show(Company $company): View
    {
        $company->load(['plan']);
        $company->loadCount(['users', 'projects', 'clients', 'invoices', 'proposals']);
        $subscriptions = $company->subscriptions()->withoutGlobalScopes()->with('plan')->latest()->get();
        $plans = Plan::query()->orderBy('name')->get();

        return view('admin.companies.show', compact('company', 'subscriptions', 'plans'));
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'confirm' => ['required', 'string'],
            'send_email' => ['sometimes', 'boolean'],
        ]);

        abort_if(trim($data['confirm']) !== $company->subdomain, 422);

        $notifyEmail = $company->contact_email;
        if (! $notifyEmail) {
            $notifyEmail = User::query()
                ->where('company_id', $company->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'company_admin'))
                ->value('email');
        }

        $company->forceFill([
            'is_enabled' => false,
            'disabled_at' => now(),
            'disabled_reason' => 'deleted_by_platform_admin',
        ])->save();

        $company->delete(); // soft delete

        if ($request->boolean('send_email') && $notifyEmail) {
            $lines = [
                'Your Flowqil workspace has been removed by the platform administrator.',
                '',
                'Workspace: '.$company->name,
                'Subdomain: '.$company->subdomain,
            ];
            try {
                Mail::raw(implode("\n", $lines), function ($m) use ($notifyEmail, $company): void {
                    $m->to($notifyEmail)->subject('Workspace removed: '.$company->name);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return redirect()->route('admin.companies.index')->with('status', __('Company removed.'));
    }

    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'disabled_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $enabled = (bool) $data['is_enabled'];

        $company->forceFill([
            'is_enabled' => $enabled,
            'disabled_at' => $enabled ? null : now(),
            'disabled_reason' => $enabled ? null : ($data['disabled_reason'] ?? null),
        ])->save();

        return redirect()->route('admin.companies.show', $company)->with('status', __('Company status updated.'));
    }

    public function updatePlan(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['nullable', Rule::exists('plans', 'id')],
            'plan_locked' => ['required', 'boolean'],
        ]);

        $company->forceFill([
            'plan_id' => $data['plan_id'] ?? null,
            'plan_locked' => (bool) $data['plan_locked'],
        ])->save();

        return redirect()->route('admin.companies.show', $company)->with('status', __('Company plan updated.'));
    }
}
