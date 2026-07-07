<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Services\AiCreditUsageService;
use App\Services\CompanyThemeService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use RuntimeException;
use Throwable;

class EmailMarketingCampaignContentAiController extends Controller
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
        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_EMAIL_CAMPAIGN);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);
        if (! $llm->isAvailable($company)) {
            return response()->json(['message' => __('ai_template_no_llm')], 422);
        }

        $data = $request->validate([
            'brief' => ['required', 'string', 'max:8000'],
        ]);

        $companyName = (string) ($company->name ?? '');
        $theme = app(CompanyThemeService::class)->themeFor($company);
        $logoUrl = is_array($theme) ? (string) ($theme['logo_url'] ?? '') : '';
        $logoHint = $logoUrl !== ''
            ? 'Use this logo URL in the header img src: '.$logoUrl
            : 'Use a header img with src="#" and alt="{{company_name}}" as logo placeholder (user will replace).';

        $system = 'You are an expert email HTML designer. Reply with a single JSON object only (no markdown fences), keys: '
            .'"subject" (compelling single-line email subject, max 200 chars), '
            .'"body_html" (complete responsive HTML email document with <!DOCTYPE html>, <html>, <head> charset/viewport, and <body>). '
            .'Design rules: table-based layout (role=presentation) max-width 600px, centered; '
            .'color palette primary blue #1e40af, dark text #111827, muted #4b5563, light background #f3f4f6; '
            .'header band in blue with centered logo image; main content area white with black/dark gray text; '
            .'clear call-to-action button in blue when appropriate; '
            .'footer with {{company_name}}, contact line (email/phone placeholders), and social network icon links (LinkedIn, Facebook, Instagram) as text links; '
            .'inline CSS only, no script tags; use merge tags {{name}}, {{first_name}}, {{company_name}} where personalisation fits; '
            .$logoHint;

        $user = "Company: {$companyName}\n\nBrief for the email campaign:\n\n".trim($data['brief']);
        try {
            $out = $llm->complete($system, $user, 12000, $company);
        } catch (RuntimeException|Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $credits = $usage->recordForTask($company, AiCreditUsageService::TASK_EMAIL_CAMPAIGN);

        $text = (string) ($out['suggestion'] ?? '');
        if ($text === '') {
            return response()->json(['message' => __('ai_template_empty')], 422);
        }

        $payload = $this->tryDecodeJsonObject($text);
        if (! is_array($payload)) {
            return response()->json(['message' => __('ai_template_invalid_json')], 422);
        }
        $subject = isset($payload['subject']) && is_string($payload['subject']) ? trim($payload['subject']) : null;
        $body = isset($payload['body_html']) && is_string($payload['body_html']) ? $payload['body_html'] : null;
        if (! $subject || $subject === '' || ! $body || $body === '') {
            return response()->json(['message' => __('ai_campaign_missing_subject_body')], 422);
        }

        return response()->json([
            'subject' => mb_substr($subject, 0, 998),
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
            if (preg_match('/\{[^{}]*"subject"[^{}]*"body_html"[^{}]*\}/s', $s, $m)) {
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
