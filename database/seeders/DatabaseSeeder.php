<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PlatformAdminSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(CurrencyRateSeeder::class);
        $this->call(ExampleDataSeeder::class);
        $this->call(ClientEmailSampleSeeder::class);
    }
}
