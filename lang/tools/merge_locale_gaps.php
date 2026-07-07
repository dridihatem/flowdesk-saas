<?php

/**
 * Merges missing keys from lang/gap_translations.php into fr/es/ar so they match en.json.
 * Run: php lang/tools/merge_locale_gaps.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);
$gaps = require __DIR__.'/gap_translations.php';

$en = json_decode(file_get_contents($base.'/en.json'), true);
if (! is_array($en)) {
    fwrite(STDERR, "Invalid en.json\n");
    exit(1);
}

foreach (['fr', 'es', 'ar', 'id', 'hi'] as $loc) {
    $path = $base.'/'.$loc.'.json';
    $data = json_decode(file_get_contents($path), true);
    if (! is_array($data)) {
        fwrite(STDERR, "Invalid {$loc}.json\n");
        exit(1);
    }
    $patch = $gaps[$loc] ?? [];
    if (! is_array($patch)) {
        fwrite(STDERR, "Invalid patch for {$loc}\n");
        exit(1);
    }
    foreach ($patch as $key => $value) {
        $data[$key] = $value;
    }
    foreach ($en as $key => $value) {
        if (! array_key_exists($key, $data)) {
            $data[$key] = $value;
        }
    }
    ksort($data, SORT_STRING);
    $out = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    file_put_contents($path, $out);
    echo "Updated {$loc}.json (".count($data)." keys)\n";
}
