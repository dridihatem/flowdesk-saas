<?php

namespace Database\Factories;

use App\Enums\ProviderPartnershipStatus;
use App\Models\Company;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'commission_rate' => fake()->randomFloat(4, 0, 0.25),
            'partnership_status' => ProviderPartnershipStatus::Active,
        ];
    }
}
