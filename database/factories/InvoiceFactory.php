<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 500_000);

        return [
            'company_id' => Company::factory(),
            'client_id' => null,
            'proposal_id' => null,
            'project_id' => null,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'subtotal_amount' => $subtotal,
            'vat_amount' => 0,
            'fiscal_stamp_amount' => 0,
            'amount' => $subtotal,
            'currency' => 'USD',
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }
}
