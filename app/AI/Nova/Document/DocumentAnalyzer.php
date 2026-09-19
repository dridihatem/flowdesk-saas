<?php

namespace App\AI\Nova\Document;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Providers\AIProvider;
use App\AI\Nova\Project\ProjectPlanner;

class DocumentAnalyzer
{
    public function __construct(
        private DocumentChunker $chunker,
        private AIProvider $ai,
        private ProjectPlanner $planner,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyzeText(string $text, NovaContext $context, string $query = ''): array
    {
        $chunks = $this->chunker->chunk($text);
        $preview = mb_substr($text, 0, 6000);

        $requirements = [
            'summary' => mb_substr(preg_replace('/\s+/', ' ', $preview) ?? $preview, 0, 400),
            'chunk_count' => count($chunks),
            'query' => $query,
        ];

        if ($this->ai->isAvailable($context->company) && mb_strlen($preview) > 40) {
            try {
                $result = $this->ai->chat(
                    'Extract structured project requirements as JSON with keys: summary, objectives (array), deliverables (array), risks (array).',
                    $preview,
                    1500,
                    $context->company
                );
                $decoded = json_decode((string) ($result['suggestion'] ?? ''), true);
                if (is_array($decoded)) {
                    $requirements = array_merge($requirements, $decoded);
                }
            } catch (\Throwable) {
                // keep heuristic summary
            }
        }

        $plan = $this->planner->fromRequirements($requirements, $context);

        return [
            'status' => 'analyzed',
            'requirements' => $requirements,
            'project_plan_preview' => $plan,
            'channels_ready' => [
                'email' => false,
                'whatsapp' => false,
                'voice' => true, // existing Nova voice stack
            ],
        ];
    }
}
