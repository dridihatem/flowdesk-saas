<?php

test('speech sanitizer strips markdown bold and list markers', function () {
    $input = '**Revenus** ce mois : 5 200 EUR. - 3 factures impayées.';

    expect(flowdesk_sanitize_speech_text($input))
        ->toBe('Revenus ce mois : 5 200 EUR. 3 factures impayées.');
});

test('speech sanitizer keeps readable punctuation and numbers', function () {
    $input = 'Growth: +12.5% vs last month (4 invoices).';

    expect(flowdesk_sanitize_speech_text($input))->toBe($input);
});

test('speech sanitizer removes markdown links and inline code', function () {
    $input = 'See [dashboard](/dash) and `code` here';

    expect(flowdesk_sanitize_speech_text($input))->toBe('See dashboard and code here');
});
