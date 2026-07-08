<?php

use App\Services\NovaVoiceInputParser;

test('extracts spoken french gmail address', function () {
    expect(NovaVoiceInputParser::extractEmail("adresse c'est amira gmail.com"))
        ->toBe('amira@gmail.com');

    expect(NovaVoiceInputParser::extractEmail('email amira arobase gmail point com'))
        ->toBe('amira@gmail.com');
});

test('extracts name from noisy french client voice input', function () {
    $input = "Complet Amira Adresse C'est Amira Gmail.com Numéro De C'est";

    expect(NovaVoiceInputParser::extractEmail($input))->toBe('amira@gmail.com');
    expect(NovaVoiceInputParser::extractName($input))->toBe('Amira');
});

test('parse name field strips french nom label', function () {
    expect(NovaVoiceInputParser::parseNameField('nom amira'))->toBe('Amira');
    expect(NovaVoiceInputParser::parseNameField('le nom est Amira Benali'))->toBe('Amira Benali');
});

test('detects multi field voice input', function () {
    expect(NovaVoiceInputParser::looksLikeMultiFieldInput('nom amira email amira@gmail.com'))
        ->toBeTrue();

    expect(NovaVoiceInputParser::looksLikeMultiFieldInput('Amira'))
        ->toBeFalse();
});
