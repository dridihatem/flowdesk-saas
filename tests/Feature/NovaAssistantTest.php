<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Services\AiWritingModesService;
use App\Services\NovaAssistantService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova assistant name uses company name and brand', function () {
    $company = Company::factory()->create(['name' => 'NovaERP']);
    $nova = app(NovaAssistantService::class);

    expect($nova->assistantName($company))->toBe('NovaERP Nova');
});

test('nova summary metrics returns dashboard figures', function () {
    $user = User::factory()->create();
    $company = $user->company;

    Client::factory()->count(3)->create(['company_id' => $company->id]);

    $summary = app(NovaAssistantService::class)->summaryMetrics($company);

    expect($summary['clients_count'])->toBeGreaterThanOrEqual(3);
    expect($summary)->toHaveKeys([
        'assistant_name',
        'active_projects',
        'monthly_revenue_minor',
        'unpaid_invoices',
        'recommendations',
    ]);
});

test('company user can open nova assistant page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('assistant.index'))
        ->assertOk()
        ->assertSee($user->company->name.' Nova')
        ->assertSee('x-data="novaAssistant', false)
        ->assertSee(__('nova_help_title'))
        ->assertSee('nova-ask-example', false)
        ->assertSee(__('nova_tab_writing'))
        ->assertSee(__('ai_writing_mode_proposal_title'))
        ->assertSee(__('ai_writing_mode_pricing_title'))
        ->assertDontSee(__('ai_writing_mode_landing_page_title'))
        ->assertSee('aiWritingModes', false);
});

test('assistant hash mode=proposal opens writing tab with proposal mode', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('assistant.index').'#mode=proposal')
        ->assertOk()
        ->assertSee(__('ai_writing_mode_proposal_title'))
        ->assertSee(__('ai_proposal_create_quote'))
        ->assertSee(__('ai_proposal_generate_lines'))
        ->assertSee('aiWritingModes', false);
});

test('assistant proposal prefill opens quote editor with outline', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_id' => $user->company_id, 'name' => 'Acme Co']);

    $this->actingAs($user)
        ->postJson(route('assistant.proposal-prefill'), [
            'client_id' => $client->id,
            'outline' => "## Scope\nWebsite redesign",
            'quote_name' => 'Acme redesign quote',
            'items' => [
                ['description' => 'Discovery workshop', 'quantity' => 1, 'unit_major' => 500],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('redirect', route('proposals.create', ['from_assistant' => 1]));

    $this->actingAs($user)
        ->get(route('proposals.create', ['from_assistant' => 1]))
        ->assertOk()
        ->assertSee('Acme redesign quote', false)
        ->assertSee('Website redesign', false)
        ->assertSee('Discovery workshop', false);
});

test('landing page writing mode is disabled by default', function () {
    config(['flowdesk.landing_page_writing_mode_enabled' => false]);

    $user = User::factory()->create();

    $modes = app(AiWritingModesService::class)->flatModesFor($user->company);

    expect(collect($modes)->pluck('mode'))->not->toContain('landing_page');
});

test('landing page writing mode can be enabled via config', function () {
    config(['flowdesk.landing_page_writing_mode_enabled' => true]);

    $user = User::factory()->create();

    $modes = app(AiWritingModesService::class)->flatModesFor($user->company);

    expect(collect($modes)->pluck('mode'))->toContain('landing_page');
});
