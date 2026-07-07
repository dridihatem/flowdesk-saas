<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', array_merge([
        'company_name' => 'Acme Testing Inc',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], validMathCaptchaFields('auth-register')));

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration rejects missing captcha', function () {
    $this->post('/register', [
        'company_name' => 'Acme Testing Inc',
        'name' => 'Test User',
        'email' => 'blocked@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('_captcha_answer');

    $this->assertGuest();
});
