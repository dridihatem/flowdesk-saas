<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'form_id' => function (array $attributes) {
                return Form::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'name' => fake()->word(),
            'type' => fake()->randomElement(['text', 'email', 'textarea', 'number']),
            'sort_order' => fake()->numberBetween(0, 50),
            'required' => fake()->boolean(30),
            'meta' => null,
        ];
    }
}
