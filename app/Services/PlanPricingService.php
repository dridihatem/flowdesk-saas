<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Collection;

class PlanPricingService
{
    public function __construct(
        private CurrencyConversionService $currency,
    ) {}

    /**
     * @param  Collection<int, Plan>  $plans
     * @return list<array{plan: Plan, periods: array<int, array{minor: int, formatted: string}>, monthly_display: array{minor: int, formatted: string}}>
     */
    public function buildPlanRows(Collection $plans, string $displayCurrency): array
    {
        $displayCurrency = strtoupper(trim($displayCurrency));
        $rows = [];

        foreach ($plans as $plan) {
            $monthlyMinorPlan = (int) round(((float) $plan->price_monthly) * 100);
            $monthlyConv = $this->currency->convertMinor($monthlyMinorPlan, $plan->currency, $displayCurrency);
            $monthlyDisplay = [
                'minor' => $monthlyConv,
                'formatted' => flowdesk_format_minor($monthlyConv, $displayCurrency).' '.$displayCurrency,
            ];

            $periods = [];
            foreach ([3, 6, 12] as $m) {
                $pp = $plan->periodPrices->firstWhere('period_months', $m);
                $minor = $pp !== null
                    ? (int) $pp->price_minor
                    : (int) round(((float) $plan->price_monthly) * 100) * $m;
                $conv = $this->currency->convertMinor($minor, $plan->currency, $displayCurrency);
                $periods[$m] = [
                    'minor' => $conv,
                    'formatted' => flowdesk_format_minor($conv, $displayCurrency).' '.$displayCurrency,
                ];
            }
            $rows[] = [
                'plan' => $plan,
                'periods' => $periods,
                'monthly_display' => $monthlyDisplay,
            ];
        }

        return $rows;
    }
}
