<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'payment_id' => null,
            'type' => fake()->randomElement(['charge', 'refund', 'adjustment']),
            'amount' => fake()->numberBetween(1000, 100_000),
            'currency' => 'USD',
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'meta' => null,
        ];
    }
}
