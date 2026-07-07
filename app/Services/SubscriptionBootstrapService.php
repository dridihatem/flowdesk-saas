<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionBootstrapService
{
    public function __construct(
        private SubscriptionTrialService $trials,
    ) {}

    public function ensureDefaultSubscription(Company $company): void
    {
        if ($company->subscriptions()->exists()) {
            return;
        }

        $plan = Plan::query()->where('slug', $this->trials->trialPlanSlug())->first()
            ?? Plan::query()->whereIn('slug', ['basic', 'starter'])->orderByRaw("CASE slug WHEN 'basic' THEN 0 WHEN 'starter' THEN 1 ELSE 2 END")->first();

        if ($plan === null) {
            return;
        }

        Subscription::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays($this->trials->trialDays()),
            'current_period_end' => now()->addDays($this->trials->trialDays()),
        ]);
    }
}
