<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\UsageTracking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageTracking>
 */
class UsageTrackingFactory extends Factory
{
    protected $model = UsageTracking::class;

    public function definition(): array
    {
        $start = fake()->dateTimeThisMonth();

        return [
            'company_id' => Company::factory(),
            'metric' => fake()->randomElement(['api_calls', 'storage_bytes', 'seats']),
            'value' => fake()->numberBetween(0, 1_000_000),
            'period_start' => $start,
            'period_end' => (clone $start)->modify('+1 month'),
        ];
    }
}
