<?php

use App\Models\Company;
use App\Models\Form;
use App\Models\FormField;
use App\Models\WidgetEvent;

function flowdeskEmbedContextB64(array $ctx): string
{
    return base64_encode(json_encode($ctx));
}

test('embed form show records view with optional page context header', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $token = $company->regenerateApiToken();
    $form = Form::factory()->create([
        'company_id' => $company->id,
        'status' => 'published',
    ]);

    $ctx = [
        'page_url' => 'https://clientsite.test/contact',
        'path' => '/contact',
        'referrer' => 'https://google.com/',
        'title' => 'Contact us',
    ];

    $response = $this->getJson("http://acme.localhost/api/v1/embed/forms/{$form->id}", [
        'Authorization' => 'Bearer '.$token,
        'X-Flowdesk-Context' => flowdeskEmbedContextB64($ctx),
    ]);

    $response->assertOk();
    $ev = WidgetEvent::query()->withoutGlobalScopes()->where('event', 'view')->first();
    expect($ev)->not->toBeNull();
    expect($ev->context['path'])->toBe('/contact');
    expect($ev->context['title'])->toBe('Contact us');
});

test('embed form submission records submit with page context header', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $token = $company->regenerateApiToken();
    $form = Form::factory()->create([
        'company_id' => $company->id,
        'status' => 'published',
    ]);
    FormField::factory()->create([
        'company_id' => $company->id,
        'form_id' => $form->id,
        'name' => 'email',
        'type' => 'email',
        'required' => true,
        'sort_order' => 0,
    ]);

    $ctx = [
        'page_url' => 'https://clientsite.test/lead',
        'path' => '/lead',
        'referrer' => null,
        'title' => 'Lead',
    ];

    $response = $this->postJson("http://acme.localhost/api/v1/embed/forms/{$form->id}/submissions", [
        'email' => 'a@example.com',
    ], [
        'Authorization' => 'Bearer '.$token,
        'X-Flowdesk-Context' => flowdeskEmbedContextB64($ctx),
    ]);

    $response->assertOk();
    $ev = WidgetEvent::query()->withoutGlobalScopes()->where('event', 'submit')->first();
    expect($ev)->not->toBeNull();
    expect($ev->context['path'])->toBe('/lead');
});

test('embed form options returns cors headers without bearer token', function () {
    $company = Company::factory()->create(['subdomain' => 'acme']);
    $form = Form::factory()->create([
        'company_id' => $company->id,
        'status' => 'published',
    ]);

    $response = $this->options("http://acme.localhost/api/v1/embed/forms/{$form->id}");

    $response->assertNoContent();
    $response->assertHeader('Access-Control-Allow-Origin', '*');
    expect($response->headers->get('Access-Control-Allow-Headers'))->toContain('X-Flowdesk-Context');
});
