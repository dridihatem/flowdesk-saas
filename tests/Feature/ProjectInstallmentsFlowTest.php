<?php

use App\Enums\ProjectInstallmentPaymentMethod;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;

test('client confirms price then company can add installment', function () {
    $company = Company::factory()->create(['default_currency' => 'USD']);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->syncRoles(['company_admin']);

    $clientUser = User::factory()->create(['company_id' => $company->id]);
    $clientUser->syncRoles(['client']);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'user_id' => $clientUser->id,
    ]);

    $project = Project::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'negotiated_price' => 10_000,
        'final_price' => null,
    ]);

    $this->actingAs($clientUser)
        ->post(route('portal.projects.confirm-price', $project))
        ->assertRedirect();

    $project->refresh();
    expect($project->client_price_confirmed_at)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('projects.installments.store', $project), [
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'amount' => '50.00',
            'payment_method' => ProjectInstallmentPaymentMethod::BankTransfer->value,
            'label' => 'Deposit',
        ])
        ->assertRedirect();

    $project->load('installments');
    expect($project->installments)->toHaveCount(1)
        ->and((int) $project->installments->first()->amount_minor)->toBe(5000);
});

test('company admin can load partnership sample terms by locale', function () {
    $company = Company::factory()->create(['name' => 'Acme Co']);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->syncRoles(['company_admin']);

    $response = $this->actingAs($admin)
        ->getJson(route('settings.provider-recruitment.sample-terms', ['locale' => 'fr']))
        ->assertOk()
        ->assertJsonStructure(['html']);

    expect($response->json('html'))->toContain('Acme Co');
});
