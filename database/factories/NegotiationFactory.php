<?php

namespace Database\Factories;

use App\Enums\NegotiationStatus;
use App\Models\Company;
use App\Models\Negotiation;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negotiation>
 */
class NegotiationFactory extends Factory
{
    protected $model = Negotiation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'proposal_id' => function (array $attributes) {
                return Proposal::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'status' => fake()->randomElement(NegotiationStatus::cases()),
            'amount' => fake()->optional()->numberBetween(1000, 100_000),
            'currency' => 'USD',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
