<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NovaSkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'PHP', 'Laravel', 'React', 'React Native', 'Vue', 'Node.js',
            'MySQL', 'PostgreSQL', 'Stripe', 'UI/UX', 'Figma', 'DevOps',
            'Docker', 'AWS', 'Azure', 'SEO', 'Marketing', 'QA',
        ];

        foreach ($skills as $name) {
            Skill::query()->firstOrCreate(
                ['company_id' => null, 'name' => $name],
                ['slug' => Str::slug($name), 'category' => 'engineering']
            );
        }
    }
}
