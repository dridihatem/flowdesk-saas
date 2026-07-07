<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Subscription;

class SubscriptionObserver
{
    public function saved(Subscription $subscription): void
    {
        $subscription->company?->syncPlanFromSubscriptions();
    }

    public function deleted(Subscription $subscription): void
    {
        $company = Company::query()->find($subscription->company_id);
        $company?->syncPlanFromSubscriptions();
    }
}
