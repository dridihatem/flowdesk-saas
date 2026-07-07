<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use App\Services\InvoicePdfService;
use App\Services\SubscriptionBootstrapService;
use App\Services\SubscriptionTrialService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PlanSeeder::class);
});

test('new company gets 14 day pro trial subscription', function () {
    $company = Company::factory()->create();

    app(SubscriptionBootstrapService::class)->ensureDefaultSubscription($company);

    $sub = Subscription::query()->where('company_id', $company->id)->first();

    expect($sub)->not->toBeNull();
    expect($sub->status)->toBe('trialing');
    expect($sub->plan?->slug)->toBe('pro');
    expect($sub->trial_ends_at?->isFuture())->toBeTrue();
    expect($sub->trial_ends_at?->isAfter(now()->addDays(13)))->toBeTrue();
});

test('trial grants pro plan limits via plan limit service', function () {
    $company = Company::factory()->create();
    app(SubscriptionBootstrapService::class)->ensureDefaultSubscription($company);

    $gates = app(\App\Services\PlanLimitService::class)->featureGates($company);

    expect($gates['analytics'])->toBeTrue();
    expect($gates['reports'])->toBeTrue();
    expect(app(SubscriptionTrialService::class)->isOnTrial($company))->toBeTrue();
});

test('invoice pdf includes payment qr when balance is due', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $client = \App\Models\Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'amount' => 25000,
        'status' => \App\Enums\InvoiceStatus::Sent,
    ]);

    $qr = flowdesk_invoice_payment_qr($invoice);

    expect($qr)->not->toBeNull();
    expect($qr['url'])->toContain('portal/invoices');
    expect($qr['data_uri'])->toStartWith('data:image/svg+xml;base64,');

    $pdf = app(InvoicePdfService::class)->output($invoice);

    expect($pdf)->not->toBeEmpty();
    expect(strlen($pdf))->toBeGreaterThan(1000);
});

test('invoice payment qr is omitted when invoice is fully paid', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'amount' => 10000,
        'status' => \App\Enums\InvoiceStatus::Paid,
    ]);

    \App\Models\Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'amount' => 10000,
        'status' => \App\Enums\PaymentStatus::Completed,
    ]);

    expect(flowdesk_invoice_payment_qr($invoice))->toBeNull();
});

test('invoice payment qr works without a linked client', function () {
    $company = Company::factory()->create(['subdomain' => 'solo']);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'amount' => 5000,
        'status' => \App\Enums\InvoiceStatus::Sent,
    ]);

    $qr = flowdesk_invoice_payment_qr($invoice);

    expect($qr)->not->toBeNull();
    expect($qr['url'])->toContain('portal/invoices');
});

test('calendar event can store custom meeting url', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)->postJson(route('calendar.events.store'), [
        'title' => 'Client call',
        'date' => now()->addDay()->toDateString(),
        'kind' => 'meeting',
        'meeting_link_type' => 'url',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ])->assertCreated()
        ->assertJsonPath('event.meeting_url', 'https://meet.google.com/abc-defg-hij');

    expect(WorkspaceCalendarEvent::query()->where('company_id', $company->id)->value('meeting_url'))
        ->toBe('https://meet.google.com/abc-defg-hij');
});
