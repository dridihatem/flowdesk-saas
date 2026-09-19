<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\PlanPeriodPrice;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Canonical product billing plans (USD major units).
     * Period totals use volume discounts: 3m full, 6m −10%, 12m −20%.
     *
     * @var array<string, array{name: string, price_monthly: int, currency: string}>
     */
    private const PLANS = [
        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 39,
            'currency' => 'USD',
        ],
        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 99,
            'currency' => 'USD',
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 249,
            'currency' => 'USD',
        ],
    ];

    public function run(): void
    {
        $starter = $this->syncPlan('starter');
        $pro = $this->syncPlan('pro');
        $enterprise = $this->syncPlan('enterprise');

        $starter->update(['addons' => []]);
        $pro->update([
            'addons' => [
                ['name' => 'Priority support', 'price_monthly_minor' => 4900, 'currency' => 'USD'],
                ['name' => 'Extra 10k submissions / mo', 'price_monthly_minor' => 1900, 'currency' => 'USD'],
            ],
        ]);
        $enterprise->update([
            'addons' => [
                ['name' => 'Dedicated success manager', 'price_monthly_minor' => 0, 'currency' => 'USD'],
                ['name' => 'Custom SLA', 'price_monthly_minor' => 0, 'currency' => 'USD'],
            ],
        ]);

        // limit_value: 0 = not included, null = unlimited quota, N = quota cap
        $this->syncPlanLimits($starter, [
            'projects' => 10,
            'users' => 5,
            'forms' => 3,
            'submissions' => 500,
            'widgets' => 3,
            'ai_credits' => 2500,
            'analytics' => 0,
            'marketing_hub' => 0,
            'email_marketing' => 0,
            'reports' => 0,
            'providers' => 0,
            'calendar' => 0,
            'modules' => 0,
            'premium_tts' => 0,
            'workspace_ai_agent' => 0,
            'hr' => 0,
        ]);

        $this->syncPlanLimits($pro, [
            'projects' => 100,
            'users' => 25,
            'forms' => 25,
            'submissions' => 10000,
            'widgets' => 25,
            'ai_credits' => 40000,
            'analytics' => 1,
            'marketing_hub' => 1,
            'email_marketing' => 1,
            'reports' => 1,
            'providers' => 25,
            'calendar' => 1,
            'modules' => 1,
            'premium_tts' => 1,
            'workspace_ai_agent' => 1,
            'hr' => 1,
        ]);

        $this->syncPlanLimits($enterprise, [
            'projects' => null,
            'users' => null,
            'forms' => null,
            'submissions' => null,
            'widgets' => null,
            'ai_credits' => null,
            'analytics' => 1,
            'marketing_hub' => 1,
            'email_marketing' => 1,
            'reports' => 1,
            'providers' => null,
            'calendar' => 1,
            'modules' => 1,
            'premium_tts' => 1,
            'workspace_ai_agent' => 1,
            'hr' => 1,
        ]);
    }

    private function syncPlan(string $slug): Plan
    {
        $attrs = self::PLANS[$slug];

        $plan = Plan::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $attrs['name'],
                'price_monthly' => $attrs['price_monthly'],
                'currency' => $attrs['currency'],
            ],
        );

        $this->syncPeriodPrices($plan);

        return $plan;
    }

    private function syncPeriodPrices(Plan $plan): void
    {
        $monthlyMajor = (int) $plan->price_monthly;
        $monthlyMinor = $monthlyMajor * 100;

        $totals = [
            3 => $monthlyMinor * 3,
            6 => (int) round($monthlyMinor * 6 * 0.90),
            12 => (int) round($monthlyMinor * 12 * 0.80),
        ];

        foreach ($totals as $months => $priceMinor) {
            PlanPeriodPrice::query()->updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'period_months' => $months,
                ],
                ['price_minor' => $priceMinor],
            );
        }
    }

    /**
     * @param  array<string, int|null>  $limits
     */
    private function syncPlanLimits(Plan $plan, array $limits): void
    {
        foreach ($limits as $featureKey => $limitValue) {
            PlanLimit::query()->updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_key' => $featureKey,
                ],
                ['limit_value' => $limitValue],
            );
        }
    }
}
