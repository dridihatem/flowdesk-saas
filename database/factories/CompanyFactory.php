<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $token = fake()->unique()->bothify('??####');

        return [
            'name' => fake()->company().' '.$token,
            'subdomain' => Str::slug('tenant-'.$token),
            'slug' => Str::slug('tenant-'.$token),
            'default_locale' => 'en',
            'default_currency' => 'USD',
        ];
    }
}
