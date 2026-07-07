<?php

use App\Enums\ProviderPartnershipStatus;
use App\Models\Company;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('guest can register as provider via recruitment slug and complete dual signature flow', function () {
    Mail::fake();
    $company = Company::factory()->create([
        'provider_recruitment_slug' => 'test-workspace',
        'provider_recruitment_enabled' => true,
        'is_enabled' => true,
    ]);

    User::factory()->create([
        'company_id' => $company->id,
        'email' => 'admin-owner@example.com',
    ])->syncRoles(['company_admin']);

    $this->get(route('partner.register.show', 'test-workspace'))->assertOk();

    $this->post(route('partner.register.store', 'test-workspace'), [
        'name' => 'Partner User',
        'email' => 'partner@example.com',
        'password' => 'Password1!x',
        'password_confirmation' => 'Password1!x',
    ])->assertRedirect(route('provider.dashboard'));

    $this->assertAuthenticated();

    $providerUser = User::query()->where('email', 'partner@example.com')->first();
    expect($providerUser)->not->toBeNull()
        ->and($providerUser->hasRole('business_provider'))->toBeTrue();

    $provider = Provider::query()->withoutGlobalScopes()->where('user_id', $providerUser->id)->first();
    expect($provider)->not->toBeNull()
        ->and($provider->partnership_status)->toBe(ProviderPartnershipStatus::PendingProvider);

    $tinyPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    $this->post(route('provider.partnership.sign'), [
        'accept' => '1',
        'signature_data' => $tinyPng,
    ])
        ->assertRedirect(route('provider.partnership.show'));

    $provider->refresh();
    expect($provider->partnership_status)->toBe(ProviderPartnershipStatus::PendingCompany)
        ->and($provider->partnership_provider_signature_data)->toBe($tinyPng);

    $admin = User::query()->where('email', 'admin-owner@example.com')->first();

    $this->actingAs($admin)
        ->post(route('providers.partnership.sign', $provider), ['accept' => '1'])
        ->assertRedirect(route('providers.index'));

    $provider->refresh();
    expect($provider->partnership_status)->toBe(ProviderPartnershipStatus::Active)
        ->and($provider->partnership_company_signed_at)->not->toBeNull();
});
