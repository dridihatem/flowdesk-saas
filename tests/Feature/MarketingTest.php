<?php

it('shows marketing home', function () {
    $this->get(route('home'))->assertOk();
});

it('shows features and about pages', function () {
    $this->get(route('marketing.features'))->assertOk();
    $this->get(route('marketing.about'))->assertOk();
});

it('redirects legacy framework url to about', function () {
    $this->get('/framework')->assertRedirect('/about');
});

it('shows pricing page', function () {
    $this->get(route('marketing.pricing'))->assertOk();
});

it('shows modules catalog page', function () {
    $this->get(route('marketing.modules'))->assertOk();
});

it('shows cart page', function () {
    $this->get(route('marketing.cart'))->assertOk();
});

it('shows contact form and accepts valid submission', function () {
    $this->get(route('marketing.contact'))->assertOk();

    $this->post(route('marketing.contact.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'company' => 'Acme',
        'message' => 'Hello from Pest.',
    ])->assertRedirect(route('marketing.contact'))
        ->assertSessionHas('status');
});

it('validates contact form', function () {
    $this->post(route('marketing.contact.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'message']);
});
