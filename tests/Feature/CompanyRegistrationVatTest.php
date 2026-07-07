<?php

use App\Models\CompanySetting;
use App\Models\User;

test('country vat map includes tunisia and france', function () {
    expect(flowdesk_vat_percent_for_country('TN'))->toBe(19.0);
    expect(flowdesk_vat_percent_for_country('FR'))->toBe(20.0);
    expect(flowdesk_vat_percent_for_country(null))->toBe(0.0);
});

test('registration saves default vat from country', function () {
    $response = $this->post('/register', array_merge([
        'company_name' => 'Tunis Services',
        'country' => 'TN',
        'name' => 'Admin User',
        'email' => 'admin-tn-vat@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], validMathCaptchaFields('auth-register')));

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'admin-tn-vat@example.com')->firstOrFail();
    $settings = CompanySetting::query()->where('company_id', $user->company_id)->firstOrFail();
    $billing = is_array($settings->billing) ? $settings->billing : [];

    expect((float) ($billing['vat_percent'] ?? 0))->toBe(19.0);
});

test('registration saves custom vat percent when provided', function () {
    $response = $this->post('/register', array_merge([
        'company_name' => 'Custom VAT Co',
        'country' => 'FR',
        'vat_percent' => 5.5,
        'name' => 'Admin User',
        'email' => 'custom-vat@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], validMathCaptchaFields('auth-register')));

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'custom-vat@example.com')->firstOrFail();
    $settings = CompanySetting::query()->where('company_id', $user->company_id)->firstOrFail();
    $billing = is_array($settings->billing) ? $settings->billing : [];

    expect($billing['vat_percent'] ?? null)->toBe(5.5);
});
