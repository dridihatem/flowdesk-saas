<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Company;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => null,
            'project_id' => null,
            'name' => fake()->sentence(4),
            'status' => fake()->randomElement(ProposalStatus::cases()),
            'amount' => fake()->numberBetween(1000, 500_000),
            'currency' => 'USD',
            'valid_until' => fake()->optional()->dateTimeBetween('now', '+60 days'),
        ];
    }
}
