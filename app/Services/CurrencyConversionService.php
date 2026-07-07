<?php

namespace App\Services;

use App\Models\CurrencyRate;

class CurrencyConversionService
{
    /**
     * Convert an amount in minor units from one ISO currency to another using USD as pivot.
     * Rates are stored as USD base → quote (units of quote per 1 USD).
     */
    public function convertMinor(int $minor, string $from, string $to): int
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        if ($from === $to) {
            return $minor;
        }

        $majorFrom = flowdesk_minor_to_major($minor, $from);

        if ($from === 'USD') {
            $usdMajor = $majorFrom;
        } else {
            $rateFrom = $this->rateUsdTo($from);
            if ($rateFrom <= 0) {
                return $minor;
            }
            $usdMajor = $majorFrom / $rateFrom;
        }

        if ($to === 'USD') {
            return (int) round($usdMajor * flowdesk_currency_minor_scale('USD'));
        }

        $rateTo = $this->rateUsdTo($to);
        if ($rateTo <= 0) {
            return $minor;
        }

        $majorTo = $usdMajor * $rateTo;

        return (int) round($majorTo * flowdesk_currency_minor_scale($to));
    }

    private function rateUsdTo(string $quote): float
    {
        if ($quote === 'USD') {
            return 1.0;
        }

        $row = CurrencyRate::query()
            ->where('base_currency', 'USD')
            ->where('quote_currency', $quote)
            ->first();

        return $row ? (float) $row->rate : 0.0;
    }
}
