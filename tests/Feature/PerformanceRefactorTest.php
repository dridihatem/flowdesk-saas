<?php

use App\Enums\InvoiceStatus;
use App\Enums\NegotiationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderRemittanceStatus;
use App\Models\Company;
use App\Models\Form;
use App\Models\Invoice;
use App\Models\Negotiation;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Proposal;
use App\Models\Provider;
use App\Models\ProviderRemittanceRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WidgetEvent;
use App\Services\AnalyticsService;
use App\Services\DashboardMetricsService;
use App\Services\MarketingInsightService;
use App\Services\PlanLimitService;
use App\Services\ProviderCommissionBalanceService;
use App\Http\Middleware\EnsureWorkspacePlanFeature;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('feature gates resolve all keys with a single subscription query', function () {
    $plan = Plan::factory()->create();
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'email_marketing',
        'limit_value' => 0,
    ]);
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'analytics',
        'limit_value' => 1,
    ]);

    $company = Company::factory()->create();
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $gates = app(PlanLimitService::class)->featureGates($company);

    $subscriptionQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains(strtolower($q['query']), 'subscriptions'))
        ->count();

    expect($subscriptionQueries)->toBe(1);
    expect($gates['email_marketing'])->toBeFalse();
    expect($gates['analytics'])->toBeTrue();
    expect($gates)->toHaveKeys(PlanLimitService::FEATURE_KEYS);
});

test('analytics monthly series uses grouped sql instead of per-month loops', function () {
    $company = Company::factory()->create();

    Invoice::factory()->create([
        'company_id' => $company->id,
        'amount' => 10000,
        'status' => InvoiceStatus::Paid,
        'created_at' => now()->startOfMonth(),
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $series = app(AnalyticsService::class)->monthlySeries($company, 3);

    $invoiceQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains(strtolower($q['query']), 'invoices'))
        ->count();

    expect($invoiceQueries)->toBe(1);
    expect($series['labels'])->toHaveCount(3);
    expect(array_sum($series['invoice_counts']))->toBeGreaterThanOrEqual(1);
});

test('dashboard metrics outstanding uses sql aggregation', function () {
    $company = Company::factory()->create();

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'amount' => 50000,
        'status' => InvoiceStatus::Sent,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'amount' => 10000,
        'status' => PaymentStatus::Completed,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $metrics = app(DashboardMetricsService::class)->forCompany($company);

    $invoiceSelectAll = collect(DB::getQueryLog())
        ->contains(fn (array $q) => str_contains(strtolower($q['query']), 'from "invoices"')
            && ! str_contains(strtolower($q['query']), 'count(')
            && ! str_contains(strtolower($q['query']), 'group by'));

    expect($invoiceSelectAll)->toBeFalse();
    expect($metrics['open_invoices_count'])->toBe(1);
    expect($metrics['outstanding_amount_minor'])->toBe(40000);
});

test('marketing widget traffic by form avoids per-form count queries', function () {
    $company = Company::factory()->create();
    $form = Form::factory()->create(['company_id' => $company->id]);

    WidgetEvent::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'form_id' => $form->id,
        'event' => 'view',
    ]);
    WidgetEvent::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'form_id' => $form->id,
        'event' => 'submit',
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $rows = app(MarketingInsightService::class)->widgetTrafficByForm($company, 30);

    $widgetEventQueries = collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains(strtolower($q['query']), 'widget_events'))
        ->count();

    expect($widgetEventQueries)->toBe(1);
    expect($rows[0]['views'])->toBe(1);
    expect($rows[0]['submits'])->toBe(1);
});

test('provider company summary uses fixed query count', function () {
    $company = Company::factory()->create();
    $provider = Provider::factory()->create(['company_id' => $company->id]);
    $proposal = Proposal::factory()->create([
        'company_id' => $company->id,
        'provider_id' => $provider->id,
    ]);

    Negotiation::factory()->create([
        'company_id' => $company->id,
        'proposal_id' => $proposal->id,
        'status' => NegotiationStatus::Accepted,
        'commission_amount_minor' => 25000,
    ]);

    ProviderRemittanceRequest::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'provider_id' => $provider->id,
        'status' => ProviderRemittanceStatus::Approved,
        'amount_minor' => 5000,
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $summary = app(ProviderCommissionBalanceService::class)->companySummary($company);

    expect(DB::getQueryLog())->toHaveCount(4);
    expect($summary['commission_total_minor'])->toBe(25000);
    expect($summary['remitted_minor'])->toBe(5000);
    expect($summary['provider_count'])->toBe(1);
});

test('ensure workspace plan feature uses request gates without extra queries', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);
    $gates['reports'] = false;

    $request = Request::create('/reports');
    $request->setUserResolver(fn () => $user);
    $request->attributes->set('flowdeskPlanGates', $gates);

    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        app(EnsureWorkspacePlanFeature::class)->handle($request, fn () => response('ok'), 'reports');
        expect(false)->toBeTrue('Expected forbidden response');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }

    expect(collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains(strtolower($q['query']), 'subscriptions'))
        ->count())->toBe(0);
});
