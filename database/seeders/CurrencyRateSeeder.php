<?php

namespace Database\Seeders;

use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    public function run(): void
    {
        $base = 'USD';

        // Static seed rates for dev/demo. Update as needed or replace with a live rates job.
        $rates = [
            'USD' => 1.0,
            'EUR' => 0.92,
            'GBP' => 0.78,
            'TND' => 3.12,
            'QAR' => 3.64,
            'CAD' => 1.36,
            'AED' => 3.67,
            'SAR' => 3.75,
        ];

        foreach ($rates as $quote => $rate) {
            CurrencyRate::query()->updateOrCreate(
                ['base_currency' => $base, 'quote_currency' => $quote],
                ['rate' => $rate, 'as_of' => now()],
            );
        }
    }
}
