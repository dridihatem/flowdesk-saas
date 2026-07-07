<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;

test('workspace api requires bearer token', function () {
    Company::factory()->create(['subdomain' => 'acme']);

    $this->getJson('http://acme.localhost/api/v1/workspace')
        ->assertUnauthorized();
});

test('workspace api me returns company info', function () {
    $company = Company::factory()->create(['subdomain' => 'acme', 'name' => 'Acme Ltd']);
    $token = $company->regenerateApiToken();

    $this->getJson('http://acme.localhost/api/v1/workspace', [
        'Authorization' => 'Bearer '.$token,
    ])
        ->assertOk()
        ->assertJsonPath('workspace.name', 'Acme Ltd')
        ->assertJsonPath('api_version', 'v1');
});

test('workspace api can create client and invoice', function () {
    $company = Company::factory()->create(['subdomain' => 'acme', 'default_currency' => 'EUR']);
    $token = $company->regenerateApiToken();

    $clientRes = $this->postJson('http://acme.localhost/api/v1/workspace/clients', [
        'name' => 'API Client',
        'email' => 'api@test.com',
    ], ['Authorization' => 'Bearer '.$token]);

    $clientRes->assertCreated();
    $clientId = $clientRes->json('data.id');
    expect($clientId)->not->toBeEmpty();

    $invoiceRes = $this->postJson('http://acme.localhost/api/v1/workspace/invoices', [
        'client_id' => $clientId,
        'currency' => 'EUR',
        'items' => [
            ['description' => 'Service', 'quantity' => 1, 'unit_amount' => 10000],
        ],
    ], ['Authorization' => 'Bearer '.$token]);

    $invoiceRes->assertCreated()
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.amount', 10000);

    expect(Invoice::query()->withoutGlobalScope('tenant')->where('company_id', $company->id)->count())->toBe(1);
});

test('workspace api import creates clients and projects', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $token = $company->regenerateApiToken();

    $response = $this->postJson('http://acme.localhost/api/v1/workspace/import', [
        'clients' => [
            ['ref' => 'ext1', 'name' => 'Imported Client', 'email' => 'imp@test.com'],
        ],
        'projects' => [
            ['title' => 'Imported Project', 'client_ref' => 'ext1'],
        ],
    ], ['Authorization' => 'Bearer '.$token]);

    $response->assertCreated()
        ->assertJsonCount(1, 'clients')
        ->assertJsonCount(1, 'projects');

    expect(Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->count())->toBe(1);
});

test('settings api connect page is available to workspace managers', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->get(route('settings.api-connect'))
        ->assertOk()
        ->assertSee(__('workspace_api_docs_heading'), false);
});
