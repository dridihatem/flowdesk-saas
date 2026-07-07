<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $starter = Plan::query()->firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'price_monthly' => 29,
                'currency' => 'USD',
            ],
        );

        $pro = Plan::query()->firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price_monthly' => 79,
                'currency' => 'USD',
            ],
        );

        $enterprise = Plan::query()->firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'price_monthly' => 199,
                'currency' => 'USD',
            ],
        );

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
            'ai_credits' => 1000,
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
            'ai_credits' => 50000,
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
