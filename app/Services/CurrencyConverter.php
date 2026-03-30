<?php

namespace App\Services;

use InvalidArgumentException;

class CurrencyConverter
{
    /**
     * Convert an amount in minor units (e.g. cents / millimes) using static USD cross-rates.
     */
    public function convert(int $amountMinor, string $from, string $to): int
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amountMinor;
        }

        $rates = config('currencies.usd_per_unit', []);

        if (! isset($rates[$from], $rates[$to])) {
            throw new InvalidArgumentException('Unknown currency rate.');
        }

        $fromMajor = $amountMinor / 100;
        $usd = $fromMajor * $rates[$from];
        $toMajor = $usd / $rates[$to];

        return (int) round($toMajor * 100);
    }
}
