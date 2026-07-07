<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'form_id' => function (array $attributes) {
                return Form::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'data' => ['message' => fake()->sentence()],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
