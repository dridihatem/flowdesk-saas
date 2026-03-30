<?php

use App\Services\CurrencyConverter;

test('currency converter converts between configured currencies', function () {
    $converter = new CurrencyConverter;

    // 100 USD minor units → EUR (rates are illustrative)
    $eur = $converter->convert(10000, 'USD', 'EUR');

    expect($eur)->toBeInt()->toBeGreaterThan(0);
});
