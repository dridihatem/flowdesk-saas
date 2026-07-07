<?php

namespace Database\Factories;

use App\Enums\PaymentEntryKind;
use App\Enums\PaymentStatus;
use App\Enums\RemittanceMethod;
use App\Models\Company;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'invoice_id' => null,
            'amount' => fake()->numberBetween(1000, 100_000),
            'currency' => 'USD',
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'payment_kind' => PaymentEntryKind::Standard,
            'payment_method' => fake()->randomElement(RemittanceMethod::cases()),
            'provider' => fake()->optional()->randomElement(['stripe', 'paypal']),
            'external_id' => fake()->optional()->uuid(),
            'paid_at' => now(),
        ];
    }
}
