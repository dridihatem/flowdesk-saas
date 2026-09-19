<?php

use App\AI\Nova\Agent\NovaAgent;
use App\AI\Nova\Autopilot\AutopilotGate;
use App\AI\Nova\Tools\ToolRegistry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Nova\NovaActionAudit;
use App\Models\Nova\NovaRun;
use App\Models\User;
use App\Enums\InvoiceStatus;
use Database\Seeders\NovaSkillSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(NovaSkillSeeder::class);
});

test('nova tool registry registers core mvp tools', function () {
    $registry = app(ToolRegistry::class);

    expect($registry->get('clients.search'))->not->toBeNull()
        ->and($registry->get('projects.create'))->not->toBeNull()
        ->and($registry->get('tasks.assign'))->not->toBeNull()
        ->and($registry->get('invoices.send'))->not->toBeNull()
        ->and($registry->get('documents.analyze'))->not->toBeNull()
        ->and($registry->get('reports.revenue'))->not->toBeNull()
        ->and($registry->get('assignments.recommend'))->not->toBeNull();
});

test('nova agent finds a client by name with tenant isolation', function () {
    $user = User::factory()->create();
    $company = $user->company;
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Ahmed Ben Ali']);
    Client::factory()->create(['name' => 'Ahmed Other Co']); // other tenant

    $response = app(NovaAgent::class)->run($user, $company, 'Find Ahmed');

    expect($response->status)->toBe(NovaRun::STATUS_COMPLETED)
        ->and($response->data['clients.search']['count'] ?? 0)->toBe(1)
        ->and($response->data['clients.search']['clients'][0]['name'])->toBe('Ahmed Ben Ali');

    expect(NovaActionAudit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count())->toBeGreaterThan(0);
});

test('nova agent clarifies when multiple clients match', function () {
    $user = User::factory()->create();
    $company = $user->company;
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Ahmed One']);
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Ahmed Two']);

    $response = app(NovaAgent::class)->run($user, $company, 'Find client Ahmed');

    expect($response->status)->toBe('clarify')
        ->and($response->clarificationOptions)->toHaveCount(2);
});

test('nova agent requires confirmation before sending invoices', function () {
    Mail::fake();
    $user = User::factory()->create();
    $company = $user->company;
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'email' => 'billing@example.com',
    ]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::Draft,
        'amount' => 1000,
    ]);

    $confirmed = app(NovaAgent::class)->run(
        $user,
        $company,
        'confirm send',
        null,
        null,
        [
            'confirmed' => true,
            'tool' => 'invoices.send',
            'arguments' => ['invoice_id' => $invoice->id],
        ]
    );

    expect($confirmed->status)->toBe(NovaRun::STATUS_COMPLETED)
        ->and($confirmed->data['invoices.send']['sent'] ?? false)->toBeTrue();
});

test('nova agent overdue invoices report works', function () {
    $user = User::factory()->create();
    $company = $user->company;
    Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::Overdue,
        'amount' => 500,
    ]);

    $response = app(NovaAgent::class)->run($user, $company, 'Show overdue invoices');

    expect($response->status)->toBe(NovaRun::STATUS_COMPLETED)
        ->and($response->data['reports.invoices']['overdue'] ?? 0)->toBeGreaterThanOrEqual(1);
});

test('nova autopilot is gated', function () {
    $user = User::factory()->create();
    $gate = app(AutopilotGate::class);
    $context = app(\App\AI\Nova\Memory\ContextBuilder::class)->build($user, $user->company);

    expect($gate->status($context)['safe_to_run'])->toBeFalse();

    expect(fn () => $gate->assertCanAutoExecute($context, 'create_project_pipeline'))
        ->toThrow(RuntimeException::class);
});

test('authenticated user can hit nova agent http endpoint', function () {
    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);
    Client::factory()->create(['company_id' => $user->company_id, 'name' => 'Acme Corp']);

    $this->actingAs($user)
        ->postJson(route('assistant.agent.run'), [
            'message' => 'Find client Acme',
            'current_page' => 'clients.index',
        ])
        ->assertOk()
        ->assertJsonStructure(['status', 'message', 'run_id', 'activities']);
});
