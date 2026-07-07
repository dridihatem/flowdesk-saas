<?php

namespace Database\Factories;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => null,
            'provider_id' => null,
            'title' => fake()->sentence(3),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'source' => ProjectSource::Internal,
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
