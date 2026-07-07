<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_id' => Plan::factory(),
            'status' => fake()->randomElement(['active', 'past_due', 'cancelled']),
            'trial_ends_at' => null,
            'current_period_end' => fake()->dateTimeBetween('+1 week', '+1 year'),
        ];
    }
}
