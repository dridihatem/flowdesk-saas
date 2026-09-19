<?php

namespace App\AI\Nova\Project;

class SkillAnalyzer
{
    /** @var array<string, list<string>> */
    private const KEYWORDS = [
        'Laravel' => ['laravel', 'php', 'eloquent'],
        'PHP' => ['php', 'composer'],
        'React' => ['react', 'jsx', 'frontend'],
        'React Native' => ['react native', 'mobile app'],
        'Vue' => ['vue', 'nuxt'],
        'Node.js' => ['node', 'express', 'nestjs'],
        'MySQL' => ['mysql', 'mariadb'],
        'PostgreSQL' => ['postgres', 'postgresql'],
        'Stripe' => ['stripe', 'payment gateway'],
        'UI/UX' => ['ui', 'ux', 'design', 'figma'],
        'Figma' => ['figma'],
        'DevOps' => ['devops', 'ci/cd', 'pipeline'],
        'Docker' => ['docker', 'container'],
        'AWS' => ['aws', 's3', 'ec2'],
        'Azure' => ['azure'],
        'SEO' => ['seo'],
        'Marketing' => ['marketing', 'campaign'],
        'QA' => ['qa', 'testing', 'test plan'],
    ];

    /**
     * @return list<string>
     */
    public function detect(string $text): array
    {
        $hay = strtolower($text);
        $found = [];
        foreach (self::KEYWORDS as $skill => $words) {
            foreach ($words as $word) {
                if (str_contains($hay, $word)) {
                    $found[] = $skill;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }
}
