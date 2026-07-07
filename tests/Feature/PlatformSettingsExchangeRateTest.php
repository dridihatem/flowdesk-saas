<?php

use App\Models\CurrencyRate;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('platform settings page lists qar in currency rates table', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)
        ->get(route('admin.platform-settings.edit'))
        ->assertOk()
        ->assertSee('QAR')
        ->assertSee(__('admin_exchange_rate_qatar_title'));
});

test('platform settings accepts comma decimal for qar rate', function () {
    $user = User::factory()->platformAdmin()->create();

    $payload = [
        'rates' => [
            ['base_currency' => 'USD', 'quote_currency' => 'QAR', 'rate' => '3,64'],
            ['base_currency' => 'USD', 'quote_currency' => 'EUR', 'rate' => '0.92'],
            ['base_currency' => 'USD', 'quote_currency' => 'GBP', 'rate' => '0.79'],
            ['base_currency' => 'USD', 'quote_currency' => 'TND', 'rate' => '3.1'],
            ['base_currency' => 'USD', 'quote_currency' => 'CAD', 'rate' => '1.36'],
            ['base_currency' => 'USD', 'quote_currency' => 'AED', 'rate' => '3.67'],
            ['base_currency' => 'USD', 'quote_currency' => 'SAR', 'rate' => '3.75'],
        ],
    ];

    $this->actingAs($user)
        ->from(route('admin.platform-settings.edit'))
        ->put(route('admin.platform-settings.update'), $payload)
        ->assertRedirect(route('admin.platform-settings.edit'));

    expect(CurrencyRate::query()->where('quote_currency', 'QAR')->value('rate'))
        ->toBe('3.64000000');
});

test('platform settings shows validation message for invalid rate', function () {
    app()->setLocale('fr');

    $user = User::factory()->platformAdmin()->create();

    $payload = [
        'rates' => [
            ['base_currency' => 'USD', 'quote_currency' => 'QAR', 'rate' => 'abc'],
            ['base_currency' => 'USD', 'quote_currency' => 'EUR', 'rate' => '0.92'],
            ['base_currency' => 'USD', 'quote_currency' => 'GBP', 'rate' => '0.79'],
            ['base_currency' => 'USD', 'quote_currency' => 'TND', 'rate' => '3.1'],
            ['base_currency' => 'USD', 'quote_currency' => 'CAD', 'rate' => '1.36'],
            ['base_currency' => 'USD', 'quote_currency' => 'AED', 'rate' => '3.67'],
            ['base_currency' => 'USD', 'quote_currency' => 'SAR', 'rate' => '3.75'],
        ],
    ];

    $this->actingAs($user)
        ->from(route('admin.platform-settings.edit'))
        ->put(route('admin.platform-settings.update'), $payload)
        ->assertSessionHasErrors(['rates.0.rate'])
        ->assertSessionHasErrors([
            'rates.0.rate' => __('admin_exchange_rate_invalid'),
        ]);
});
