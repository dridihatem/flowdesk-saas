<?php

namespace App\AI\Nova\Project;

class PriorityAnalyzer
{
    public function infer(string $title, string $description = ''): string
    {
        $hay = strtolower($title.' '.$description);
        if (preg_match('/\b(urgent|critical|asap|blocker|security|payment|launch)\b/', $hay)) {
            return 'high';
        }
        if (preg_match('/\b(nice to have|optional|later|polish)\b/', $hay)) {
            return 'low';
        }

        return 'medium';
    }
}
