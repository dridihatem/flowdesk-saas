<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionTrialService
{
    public function trialDays(): int
    {
        return max(1, (int) config('flowdesk.trial_days', 14));
    }

    public function trialPlanSlug(): string
    {
        return (string) config('flowdesk.trial_plan_slug', 'pro');
    }

    public function activeSubscription(Company $company): ?Subscription
    {
        return $company->subscriptions()
            ->withoutGlobalScopes()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan.limits')
            ->latest('id')
            ->first();
    }

    public function isOnTrial(Company $company): bool
    {
        $sub = $this->activeSubscription($company);
        if ($sub === null || $sub->trial_ends_at === null) {
            return false;
        }

        return $sub->trial_ends_at->isFuture()
            && in_array($sub->status, ['trialing', 'active'], true);
    }

    public function trialExpired(Company $company): bool
    {
        $sub = $this->activeSubscription($company);
        if ($sub === null || $sub->trial_ends_at === null) {
            return false;
        }

        return $sub->trial_ends_at->isPast()
            && ! $this->hasPaidBilling($company);
    }

    public function hasPaidBilling(Company $company): bool
    {
        return filled($company->stripe_customer_id);
    }

    /**
     * Plan used for limits/features — Pro during trial, otherwise subscribed plan (or starter after expiry).
     */
    public function effectivePlan(Company $company): ?Plan
    {
        $sub = $this->activeSubscription($company);

        if ($sub === null) {
            return null;
        }

        if ($this->isOnTrial($company)) {
            $trialPlan = Plan::query()->with('limits')->where('slug', $this->trialPlanSlug())->first();
            if ($trialPlan !== null) {
                return $trialPlan;
            }
        }

        if ($this->trialExpired($company)) {
            return Plan::query()->with('limits')->where('slug', 'starter')->first()
                ?? $sub->plan;
        }

        $sub->plan?->loadMissing('limits');

        return $sub->plan;
    }

    /**
     * @return array{show: bool, days_left: int, ends_at: ?string, expired: bool, plan_name: ?string}
     */
    public function bannerFor(Company $company): array
    {
        $sub = $this->activeSubscription($company);

        if ($sub === null || $sub->trial_ends_at === null) {
            return [
                'show' => false,
                'days_left' => 0,
                'ends_at' => null,
                'expired' => false,
                'plan_name' => null,
            ];
        }

        if ($this->hasPaidBilling($company)) {
            return [
                'show' => false,
                'days_left' => 0,
                'ends_at' => null,
                'expired' => false,
                'plan_name' => null,
            ];
        }

        $expired = $this->trialExpired($company);
        $daysLeft = $expired
            ? 0
            : max(0, (int) now()->diffInDays($sub->trial_ends_at, false));

        return [
            'show' => $this->isOnTrial($company) || $expired,
            'days_left' => $daysLeft,
            'ends_at' => $sub->trial_ends_at->toDateString(),
            'expired' => $expired,
            'plan_name' => Plan::query()->where('slug', $this->trialPlanSlug())->value('name'),
        ];
    }
}
