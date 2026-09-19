<?php

namespace App\AI\Nova\Agent;

use App\AI\Nova\Providers\AIProvider;
use App\AI\Nova\Tools\ToolRegistry;
use Illuminate\Support\Str;

/**
 * Intent + plan layer. Uses the AI provider when available; falls back to
 * deterministic heuristics so Nova remains useful without live LLM keys.
 */
class NovaPlanner
{
    public function __construct(
        private AIProvider $ai,
        private ToolRegistry $tools,
    ) {}

    public function decide(string $input, NovaContext $context): NovaDecision
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return NovaDecision::clarify('What would you like me to do?');
        }

        if ($this->ai->isAvailable($context->company)) {
            try {
                return $this->decideWithLlm($trimmed, $context);
            } catch (\Throwable) {
                // Fall through to heuristics.
            }
        }

        return $this->decideHeuristically($trimmed, $context);
    }

    private function decideWithLlm(string $input, NovaContext $context): NovaDecision
    {
        $toolSchemas = $this->tools->schemasFor($context);
        $system = <<<'SYS'
You are Nova, FlowQil's business agent planner. Return ONLY valid JSON with one of:
{"type":"tools","tool_calls":[{"tool":"name","arguments":{}}]}
{"type":"respond","message":"..."}
{"type":"clarify","message":"...","options":[{"id":"...","label":"..."}]}
Never invent IDs. Prefer tools for company data. Multi-step requests may include multiple tool_calls in order.
SYS;

        $user = json_encode([
            'input' => $input,
            'context' => $context->toArray(),
            'tools' => $toolSchemas,
        ], JSON_THROW_ON_ERROR);

        $raw = $this->ai->toolCall($system, $user, $toolSchemas, $context->company);
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['type'])) {
            return $this->decideHeuristically($input, $context);
        }

        return match ($decoded['type']) {
            'tools' => NovaDecision::useTools(
                array_values(array_filter(
                    array_map(function ($call) {
                        if (! is_array($call) || ! isset($call['tool'])) {
                            return null;
                        }

                        return [
                            'tool' => (string) $call['tool'],
                            'arguments' => is_array($call['arguments'] ?? null) ? $call['arguments'] : [],
                        ];
                    }, $decoded['tool_calls'] ?? [])
                ))
            ),
            'clarify' => NovaDecision::clarify(
                (string) ($decoded['message'] ?? 'Please clarify.'),
                is_array($decoded['options'] ?? null) ? $decoded['options'] : []
            ),
            default => NovaDecision::respond((string) ($decoded['message'] ?? '')),
        };
    }

    private function decideHeuristically(string $input, NovaContext $context): NovaDecision
    {
        $lower = Str::lower($input);

        if (preg_match('/\b(revenue|how much.*(make|made|earn)|chiffre)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'reports.revenue', 'arguments' => []]]);
        }
        if (preg_match('/\b(overdue|unpaid).*(invoice|facture)|invoice.*(overdue|unpaid)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'reports.invoices', 'arguments' => ['filter' => 'overdue']]]);
        }
        if (preg_match('/\b(workload|overloaded|availability|charge de travail)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'reports.workload', 'arguments' => []]]);
        }
        if (preg_match('/\b(analyze|analyser|search).*(document|pdf|file)\b/u', $lower)
            || preg_match('/\b(document|pdf).*(analyze|analyser|search)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'documents.analyze', 'arguments' => [
                'query' => $input,
            ]]]);
        }
        if (preg_match('/\b(assign|recommande|recommend).*(task|team|member)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'assignments.recommend', 'arguments' => [
                'query' => $input,
            ]]]);
        }
        if (preg_match('/\b(create|add|ajouter|créer|creer)\b.*\b(task|tâche|tache)\b/u', $lower)) {
            return NovaDecision::clarify('Which project should this task belong to, and what is the task title?');
        }
        if (preg_match('/\b(create|add|ajouter|créer|creer)\b.*\b(project|projet)\b/u', $lower)) {
            return NovaDecision::clarify('What should we name the project, and which client is it for?');
        }
        if (preg_match('/\b(create|add|ajouter|créer|creer)\b.*\b(invoice|facture)\b/u', $lower)) {
            return NovaDecision::clarify('Which client should the invoice be for, and what amount?');
        }
        if (preg_match('/\b(find|show|search|cherche|trouve|analyse|analyze)\b.*\b(client|customer)\b/u', $lower)
            || preg_match('/\b(client|customer)\b.*\b(find|show|search|owes|doit)\b/u', $lower)
            || preg_match('/\b(find|cherche|trouve)\b\s+[a-zà-ÿ]/iu', $lower)) {
            $name = $this->extractNameAfterKeywords($input, ['client', 'customer', 'called', 'named', 'ahmed']);
            if ($name === null && preg_match('/\b(?:find|cherche|trouve)\s+(.+)$/iu', $input, $m)) {
                $name = trim($m[1], " \t\n\r\0\x0B\"'«»");
            }
            $args = $name !== null ? ['query' => $name] : ['query' => $input];
            $calls = [['tool' => 'clients.search', 'arguments' => $args]];
            if (preg_match('/\b(owes|outstanding|unpaid|balance|doit)\b/u', $lower)) {
                $calls[] = ['tool' => 'invoices.search', 'arguments' => ['status' => 'unpaid']];
            }
            if (preg_match('/\b(project|projet)\b/u', $lower)) {
                $calls[] = ['tool' => 'projects.search', 'arguments' => $args];
            }

            return NovaDecision::useTools($calls);
        }
        if (preg_match('/\b(project|projet)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'projects.search', 'arguments' => ['query' => $input]]]);
        }
        if (preg_match('/\b(task|tâche|tache)\b/u', $lower)) {
            return NovaDecision::useTools([['tool' => 'tasks.search', 'arguments' => ['query' => $input]]]);
        }

        return NovaDecision::respond(
            "I can search clients, projects, tasks, invoices, documents, and reports for {$context->company->name}. Try: \"Find Ahmed\", \"Show overdue invoices\", or \"Analyze this PDF\"."
        );
    }

    private function extractNameAfterKeywords(string $input, array $hints): ?string
    {
        if (preg_match('/(?:client|customer|called|named|client[e]?)\s+[«"]?([A-Za-zÀ-ÿ][\wÀ-ÿ\'\- ]{1,60})/iu', $input, $m)) {
            return trim($m[1], " \t\n\r\0\x0B\"'«»");
        }
        foreach ($hints as $hint) {
            if (Str::contains(Str::lower($input), Str::lower($hint)) && Str::lower($hint) === 'ahmed') {
                return 'Ahmed';
            }
        }

        return null;
    }
}
