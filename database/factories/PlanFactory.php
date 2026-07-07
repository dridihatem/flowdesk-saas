<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' plan';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify('###')),
            'price_monthly' => fake()->numberBetween(0, 999_00),
            'currency' => 'USD',
        ];
    }
}
