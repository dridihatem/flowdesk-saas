<?php

use App\Models\CurrencyRate;
use App\Services\CurrencyConversionService;
use Database\Seeders\CurrencyRateSeeder;

beforeEach(function () {
    $this->seed(CurrencyRateSeeder::class);
});

test('qar is available in currency select options', function () {
    $options = flowdesk_currency_select_options();

    expect($options)->toHaveKey('QAR');
    expect($options['QAR'])->toContain('QAR');
});

test('currency conversion uses qatar rate from database', function () {
    CurrencyRate::query()->updateOrCreate(
        ['base_currency' => 'USD', 'quote_currency' => 'QAR'],
        ['rate' => '3.64', 'as_of' => now()],
    );

    $service = app(CurrencyConversionService::class);
    $converted = $service->convertMinor(10000, 'USD', 'QAR'); // 100.00 USD

    expect($converted)->toBe(36400); // 364.00 QAR
});

test('qatar company registration maps to qar default currency', function () {
    expect(config('flowdesk.country_currency.QA'))->toBe('QAR');
});
