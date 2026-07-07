<?php

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use App\Services\NovaClientAnalysisService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova client analysis detects analyze clients requests', function () {
    $service = app(NovaClientAnalysisService::class);

    expect($service->isClientAnalysisRequest('Analyze clients'))->toBeTrue();
    expect($service->isClientAnalysisRequest('Analyse client Acme Holdings'))->toBeTrue();
    expect($service->isClientAnalysisRequest('How many clients do we have?'))->toBeFalse();
});

test('nova client analysis extracts client name from voice phrase', function () {
    $service = app(NovaClientAnalysisService::class);

    expect($service->extractClientNameCandidate('Analyze client called Acme Holdings'))
        ->toBe('Acme Holdings');
    expect($service->extractClientNameCandidate('Analyse the client Nova Labs please'))
        ->toBe('Nova Labs');
});

test('nova client analysis summarizes a named client', function () {
    $user = User::factory()->create();
    $company = $user->company;

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Acme Holdings',
        'email' => 'billing@acme.test',
    ]);

    Project::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'title' => 'Website redesign',
        'status' => ProjectStatus::InProgress->value,
    ]);

    Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::Sent,
        'amount' => 120_000,
        'currency' => 'USD',
    ]);

    WorkspaceCalendarEvent::query()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'title' => 'Kickoff call',
        'starts_on' => now()->addDays(3)->toDateString(),
        'kind' => 'meeting',
        'meeting_link_type' => 'google_meet',
        'google_meet_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    $reply = app(NovaClientAnalysisService::class)->tryReply(
        $company,
        $user,
        'Analyze client Acme Holdings',
    );

    expect($reply)
        ->toContain('Acme Holdings')
        ->toContain('Website redesign')
        ->toContain('billing@acme.test')
        ->toContain('Kickoff call');
});

test('nova client analysis returns overview when no client name given', function () {
    $user = User::factory()->create();
    $company = $user->company;

    Client::factory()->count(2)->create(['company_id' => $company->id]);

    $reply = app(NovaClientAnalysisService::class)->tryReply(
        $company,
        $user,
        'Analyze clients',
    );

    expect($reply)->toContain('2');
});

test('assistant chat returns client analysis without charging credits', function () {
    $user = User::factory()->create();
    $company = $user->company;

    Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Beta Corp',
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('assistant.chat'), [
            'message' => 'Analyze client Beta Corp',
        ])
        ->assertOk()
        ->assertJsonPath('client_analysis', true)
        ->assertJsonPath('ai_credits_charged', 0);

    expect($response->json('reply'))->toContain('Beta Corp');
});

test('nova intent matches client name from analyze client phrase', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Gamma Industries',
    ]);

    $intent = app(\App\Services\NovaQuestionIntentService::class);
    $result = $intent->analyze($company, 'Analyze client Gamma Industries');

    expect($result['client_ids'])->toContain($client->id);
});
