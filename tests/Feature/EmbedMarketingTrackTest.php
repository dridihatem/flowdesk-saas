<?php

use App\Models\Company;
use App\Models\WidgetEvent;

test('embed track records pageview with valid token and tenant host', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $token = $company->regenerateApiToken();

    $payload = [
        'page_url' => 'https://example.com/pricing?x=1',
        'path' => '/pricing?x=1',
        'referrer' => 'https://google.com/',
        'title' => 'Pricing — Example',
    ];

    $response = $this->postJson('http://acme.localhost/api/v1/embed/track', $payload, [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    expect(WidgetEvent::query()->withoutGlobalScopes()->count())->toBe(1);
    $ev = WidgetEvent::query()->withoutGlobalScopes()->first();
    expect($ev->event)->toBe('pageview');
    expect($ev->form_id)->toBeNull();
    expect($ev->context['path'])->toBe('/pricing?x=1');
});

test('embed track rejects invalid token', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $company->regenerateApiToken();

    $response = $this->postJson('http://acme.localhost/api/v1/embed/track', [
        'page_url' => 'https://example.com/',
    ], [
        'Authorization' => 'Bearer fd_live_wrongtokenxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    ]);

    $response->assertUnauthorized();
});
