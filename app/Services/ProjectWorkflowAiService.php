<?php

namespace App\Services;

use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProjectWorkflowAiService
{
    private const MAX_AUTO_TASKS = 12;

    public function __construct(
        private PlatformLlmRouter $llm,
    ) {}

    /**
     * One LLM call: rewrite project description (HTML) and suggest tasks, saved in one transaction.
     *
     * @return array{0: int, 1: array<string, mixed>} [tasks created, LLM result (includes token usage fields)]
     */
    public function generateDescriptionAndTasks(Project $project): array
    {
        $plain = $this->plainTextFromHtml((string) $project->description);
        if ($plain === '' && $project->title === '') {
            throw new RuntimeException(__('Add a title or description before generating.'));
        }

        $clientName = $project->client?->name ?? '';
        $providerName = $project->provider?->name ?? '';

        $project->loadMissing('company');

        $userBlock = 'Project title: '.$project->title."\n"
            .'Client: '.($clientName !== '' ? $clientName : '—')."\n"
            .'Provider: '.($providerName !== '' ? $providerName : '—')."\n"
            .'Final deadline: '.($project->final_deadline?->format('Y-m-d') ?? '—')."\n\n"
            ."Current description (plain text):\n".($plain !== '' ? $plain : '(none — infer scope from title and context only)');

        $system = <<<'PROMPT'
You are an expert project and delivery manager. Respond with ONLY valid JSON (no markdown, no code fences, no text before or after the JSON).

Use this exact structure:
{
  "description_html": "<p>...</p>",
  "tasks": [{"title":"...","description":"..."}]
}

Rules for description_html:
- Valid HTML fragment only (no <html> or <body>). Use <p>, <ul>, <li>, <strong> where helpful.
- Cover goals, scope, key deliverables, assumptions, and risks or dependencies if inferable.
- Use the output language below; if the project text is clearly in one other language only, use that for the HTML.

Rules for tasks:
- 4–12 items, preferably 4–10. Each title is actionable and verb-led; tasks must not overlap.
- Optional one-line "description" per task (empty string allowed).
- Use the same language as description_html.

Escape any double quotes inside HTML attribute values as needed so the JSON is valid.
PROMPT;
        $system .= "\n\n".AiAssistantPrompts::workflowJsonOutputLanguageRules();

        $result = $this->llm->complete($system, $userBlock, 8192, $project->company);
        $decoded = $this->decodeJsonObject($result['suggestion']);

        $htmlRaw = $decoded['description_html'] ?? $decoded['description'] ?? null;
        if (! is_string($htmlRaw)) {
            throw new RuntimeException(__('The AI response did not include a description.'));
        }

        $html = $this->normalizeDescriptionHtml($htmlRaw);

        $items = $decoded['tasks'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $items = array_slice($items, 0, self::MAX_AUTO_TASKS);

        $created = 0;

        DB::transaction(function () use ($project, $html, $items, &$created): void {
            $project->forceFill(['description' => $html])->save();

            $maxOrder = (int) $project->tasks()->where('status', TaskStatus::Todo)->max('sort_order');
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $title = isset($row['title']) ? trim((string) $row['title']) : '';
                if ($title === '') {
                    continue;
                }
                $title = Str::limit($title, 255, '');
                $desc = isset($row['description']) ? trim((string) $row['description']) : '';
                $desc = $desc === '' ? null : Str::limit($desc, 5000, '');

                $maxOrder++;
                ProjectTask::query()->create([
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'title' => $title,
                    'description' => $desc,
                    'status' => TaskStatus::Todo,
                    'sort_order' => $maxOrder,
                    'scope' => TaskScope::Core,
                    'price_mode' => TaskPriceMode::Bundled,
                    'billable' => false,
                    'amount_cents' => null,
                    'currency' => null,
                ]);
                $created++;
            }
        });

        return [$created, $result];
    }

    private function plainTextFromHtml(string $html): string
    {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return $t;
    }

    private function normalizeDescriptionHtml(string $raw): string
    {
        $t = trim($raw);
        if ($t === '') {
            throw new RuntimeException(__('The model returned an empty description.'));
        }
        if (str_starts_with($t, '```')) {
            $t = preg_replace('/^```(?:html)?\s*/i', '', $t) ?? $t;
            $t = preg_replace('/\s*```$/', '', $t) ?? $t;
            $t = trim($t);
        }
        if ($t !== '' && ! preg_match('/<[a-z][\s\S]*>/i', $t)) {
            return '<p>'.e($t).'</p>';
        }

        return $t;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $raw): array
    {
        $text = trim($raw);
        if (preg_match('/```(?:json)?\s*([\s\S]*)\s*```/', $text, $m)) {
            $text = trim($m[1]);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException(__('Could not parse AI response.'));
        }
        $slice = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($slice, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(__('Could not parse AI response.'));
        }

        return $decoded;
    }
}
