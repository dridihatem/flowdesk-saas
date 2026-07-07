<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MarketingSupport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingSupport>
 */
class MarketingSupportFactory extends Factory
{
    protected $model = MarketingSupport::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'status' => fake()->randomElement(['open', 'in_progress', 'closed']),
        ];
    }
}
