<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    protected $model = CompanySetting::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branding' => ['logo_url' => null],
            'smtp' => null,
            'theme' => ['mode' => 'light'],
            'payment_credentials' => null,
        ];
    }
}
