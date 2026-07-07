<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(['card', 'bank_transfer', 'wallet']),
            'name' => fake()->creditCardType(),
            'meta' => ['last4' => fake()->numerify('####')],
            'is_default' => false,
        ];
    }
}
