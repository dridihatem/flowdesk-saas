<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true).' form',
            'status' => fake()->randomElement(['draft', 'published']),
            'layout' => 'simple',
            'widget_version' => 1,
        ];
    }
}
