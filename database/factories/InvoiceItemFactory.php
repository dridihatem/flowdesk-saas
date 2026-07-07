<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 10);
        $unit = fake()->numberBetween(1000, 50_000);

        return [
            'company_id' => Company::factory(),
            'invoice_id' => function (array $attributes) {
                return Invoice::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'description' => fake()->sentence(4),
            'quantity' => $qty,
            'unit_amount' => $unit,
            'total_amount' => $qty * $unit,
        ];
    }
}
