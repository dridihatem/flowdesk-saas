<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Company;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'source' => 'manual',
            'status' => InquiryStatus::New,
        ];
    }
}
