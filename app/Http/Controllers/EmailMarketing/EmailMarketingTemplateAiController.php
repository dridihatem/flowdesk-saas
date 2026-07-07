<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Services\AiCreditUsageService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use RuntimeException;
use Throwable;

class EmailMarketingTemplateAiController extends Controller
{
    public function __invoke(
        Request $request,
        PlatformLlmRouter $llm,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
    ): JsonResponse {
        $company = $request->user()?->company;
        if (! $company) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_EMAIL_TEMPLATE);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);
        if (! $llm->isAvailable($company)) {
            return response()->json(['message' => __('ai_template_no_llm')], 422);
        }

        $data = $request->validate([
            'brief' => ['required', 'string', 'max:8000'],
        ]);

        $system = 'You are an expert email copywriter. Reply with a single JSON object only (no markdown fences), with keys: '
            .'"name" (short template name, under 100 chars), '
            .'"body_html" (a compact HTML body suitable for an email, use a single wrapper with inline-friendly tags like h1, p, ul, a, table; no script tags; do not use outer html/head/body; assume UTF-8).';

        $user = "Brief for the email template:\n\n".trim($data['brief']);
        try {
            $out = $llm->complete($system, $user, 12000, $company);
        } catch (RuntimeException|Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $credits = $usage->recordForTask($company, AiCreditUsageService::TASK_EMAIL_TEMPLATE);

        $text = (string) ($out['suggestion'] ?? '');
        if ($text === '') {
            return response()->json(['message' => __('ai_template_empty')], 422);
        }

        $payload = $this->tryDecodeJsonObject($text);
        if (! is_array($payload)) {
            return response()->json(['message' => __('ai_template_invalid_json')], 422);
        }
        $name = isset($payload['name']) && is_string($payload['name']) ? trim($payload['name']) : null;
        $body = isset($payload['body_html']) && is_string($payload['body_html']) ? $payload['body_html'] : null;
        if (! $name || $name === '' || ! $body || $body === '') {
            return response()->json(['message' => __('ai_template_missing_keys')], 422);
        }

        return response()->json([
            'name' => mb_substr($name, 0, 200),
            'body_html' => $body,
            'ai_credits_charged' => $credits,
        ]);
    }

    private function tryDecodeJsonObject(string $raw): ?array
    {
        $s = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $s, $m)) {
            $s = $m[1];
        }
        try {
            $d = json_decode($s, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            if (preg_match('/\{[^{}]*"name"[^{}]*"body_html"[^{}]*\}/s', $s, $m)) {
                try {
                    $d = json_decode($m[0], true, 8, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    return null;
                }
            } else {
                return null;
            }
        }
        if (! is_array($d)) {
            return null;
        }

        return $d;
    }
}
