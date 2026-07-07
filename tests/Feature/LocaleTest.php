<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('guest can change session locale', function () {
    $response = $this->from('/login')->post('/locale', ['locale' => 'fr']);

    $response->assertRedirect('/login');
    expect(session('locale'))->toBe('fr');
});

test('authenticated user can persist locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->post('/locale', ['locale' => 'es']);

    expect($user->fresh()->locale)->toBe('es');
});

test('speech recognition locale maps supported app locales', function () {
    expect(flowdesk_speech_recognition_locale('en'))->toBe('en-US');
    expect(flowdesk_speech_recognition_locale('fr'))->toBe('fr-FR');
    expect(flowdesk_speech_recognition_locale('es'))->toBe('es-ES');
    expect(flowdesk_speech_recognition_locale('ar'))->toBe('ar-SA');
    expect(flowdesk_speech_recognition_locale('id'))->toBe('id-ID');
    expect(flowdesk_speech_recognition_locale('hi'))->toBe('hi-IN');
    expect(flowdesk_is_voice_locale_supported('fr'))->toBeTrue();
    expect(flowdesk_is_voice_locale_supported('id'))->toBeTrue();
    expect(flowdesk_is_voice_locale_supported('hi'))->toBeTrue();
    expect(flowdesk_locale_name('id'))->toBe('Bahasa Indonesia');
    expect(flowdesk_locale_name('hi'))->toBe('हिन्दी');
});

test('guest locale is auto detected from ip country header once per session', function () {
    $this->withHeaders(['CF-IPCountry' => 'FR'])
        ->get('/login')
        ->assertOk();

    expect(session('locale'))->toBe('fr');
    expect(session('locale_auto_pinned'))->toBeTrue();

    $this->withHeaders(['CF-IPCountry' => 'US'])
        ->get('/login')
        ->assertOk();

    expect(session('locale'))->toBe('fr');
});

test('company default locale overrides ip detection for staff', function () {
    $user = User::factory()->create();
    $user->company->update(['default_locale' => 'es']);
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->withHeaders(['CF-IPCountry' => 'FR'])
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('es');
});

test('workspace can save default language in settings', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->put(route('settings.workspace-locale.update'), ['default_locale' => 'fr'])
        ->assertRedirect(route('settings.workspace-locale'));

    expect($user->company->fresh()->default_locale)->toBe('fr');
});

test('locale detection maps country to supported locale', function () {
    $service = app(\App\Services\LocaleDetectionService::class);

    expect($service->localeForCountry('FR'))->toBe('fr');
    expect($service->localeForCountry('TN'))->toBe('fr');
    expect($service->localeForCountry('QA'))->toBe('ar');
    expect($service->localeForCountry('IN'))->toBe('hi');
});

test('arabic locale uses western number formatting like english', function () {
    app()->setLocale('ar');

    expect(flowdesk_locale_amount_separators('ar'))->toBe(['decimal' => '.', 'thousands' => ',']);
    expect(flowdesk_format_minor(238000, 'EUR'))->toBe('2,380.00');
});

test('french locale keeps european number formatting', function () {
    app()->setLocale('fr');

    expect(flowdesk_locale_amount_separators('fr'))->toBe(['decimal' => ',', 'thousands' => ' ']);
    expect(flowdesk_format_minor(238000, 'EUR'))->toBe('2 380,00');
});

test('arabic invoice and quote list tables use logical rtl alignment', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->withSession(['locale' => 'ar'])
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('table-fixed text-start text-sm', false)
        ->assertSee(__('Client'), false);

    $this->actingAs($user)
        ->withSession(['locale' => 'ar'])
        ->get(route('clients.index'))
        ->assertOk()
        ->assertSee('flow-data-table', false)
        ->assertSee('table-fixed', false);

    $this->actingAs($user)
        ->withSession(['locale' => 'ar'])
        ->get(route('proposals.index'))
        ->assertOk()
        ->assertSee('table-fixed text-start text-sm', false)
        ->assertSee(__('Amount'), false);
});
