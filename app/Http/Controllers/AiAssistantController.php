<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Services\AiAssistantPrompts;
use App\Services\AiCreditUsageService;
use App\Services\AiWritingModesService;
use App\Services\NovaAssistantService;
use App\Services\NovaClientAnalysisService;
use App\Services\NovaIdentityService;
use App\Services\NovaTextToSpeechService;
use App\Services\NovaVoiceBriefingService;
use App\Services\NovaVoiceWorkflowService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use App\Services\ProposalWritingContextService;
use App\Services\WorkspaceAiConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AiAssistantController extends Controller
{
    public function index(Request $request, NovaAssistantService $nova, AiWritingModesService $writingModes): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $conversations = $nova->recentConversations($company, $request->user());
        $canQuotes = $request->user()->can('workspace.manage_invoices');

        $clients = $canQuotes
            ? Client::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'code'])
            : collect();

        return view('assistant.index', [
            'assistantName' => $nova->assistantName($company),
            'summary' => $nova->summaryMetrics($company),
            'conversations' => $conversations,
            'chatUrl' => route('assistant.chat'),
            'summaryUrl' => route('assistant.summary'),
            'suggestUrl' => route('assistant.suggest'),
            'speakUrl' => route('assistant.speak'),
            'creditCost' => app(AiCreditUsageService::class)->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'nova_chat'),
            'writingModeGroups' => $writingModes->groupsFor($company),
            'writingModes' => $writingModes->flatModesFor($company),
            'proposalClients' => $clients,
            'proposalQuoteDraftUrl' => $canQuotes && Route::has('proposals.ai-line-items.draft')
                ? route('proposals.ai-line-items.draft')
                : null,
            'proposalPrefillUrl' => $canQuotes ? route('assistant.proposal-prefill') : null,
            'proposalClientContextUrl' => route('assistant.proposal-client-context'),
            'defaultCurrency' => strtoupper((string) ($company->default_currency ?? 'USD')),
        ]);
    }

    public function summary(Request $request, NovaAssistantService $nova): JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        return response()->json($nova->summaryMetrics($company));
    }

    public function chat(
        Request $request,
        NovaAssistantService $nova,
        NovaIdentityService $identity,
        NovaClientAnalysisService $clientAnalysis,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        PlatformLlmRouter $llm,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'string', 'max:26'],
        ]);

        if ($identity->isIdentityQuestion($data['message'])) {
            $reply = $identity->reply($company, $request->user());

            return response()->json([
                'reply' => $reply,
                'conversation_id' => $data['conversation_id'] ?? null,
                'model' => 'canned',
                'disclaimer' => '',
                'ai_credits_charged' => 0,
                'ai_credits_cost' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'identity' => true,
            ]);
        }

        $clientAnalysisReply = $clientAnalysis->tryReply($company, $request->user(), $data['message']);
        if ($clientAnalysisReply !== null) {
            return response()->json([
                'reply' => $clientAnalysisReply,
                'conversation_id' => $data['conversation_id'] ?? null,
                'model' => 'canned',
                'disclaimer' => '',
                'ai_credits_charged' => 0,
                'ai_credits_cost' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'client_analysis' => true,
            ]);
        }

        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'nova_chat');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        if (! $llm->isAvailable($company)) {
            return response()->json([
                'message' => app(WorkspaceAiConfigService::class)->unavailableMessage($company),
            ], 503);
        }

        try {
            $result = $nova->chat(
                $company,
                $request->user(),
                $data['message'],
                $data['conversation_id'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $credits = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, 'nova_chat');

        return response()->json([
            'reply' => $result['reply'],
            'conversation_id' => $result['conversation_id'],
            'model' => $result['model'],
            'disclaimer' => __('AI-generated content — review before sending to clients.'),
            'ai_credits_charged' => $credits,
            'ai_credits_cost' => $creditCost,
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'total_tokens' => $result['total_tokens'],
        ]);
    }

    public function suggest(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        PlatformLlmRouter $llm,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'mode' => ['required', 'string', 'in:proposal,pricing,form,summary,ticket,client_email,task_followup,seo,project_description,growth_projects,growth_invoices,growth_clients,report_counsel,landing_page'],
            'context' => ['nullable', 'string', 'max:12000'],
        ]);

        if ($data['mode'] === 'landing_page' && ! config('flowdesk.landing_page_writing_mode_enabled')) {
            return response()->json([
                'message' => __('ai_landing_page_mode_disabled'),
            ], 403);
        }

        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, $data['mode']);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        if (! $llm->isAvailable($company)) {
            return response()->json([
                'message' => app(WorkspaceAiConfigService::class)->unavailableMessage($company),
            ], 503);
        }

        $ctx = $data['context'] ?? '';

        try {
            if ($data['mode'] === 'landing_page') {
                $result = $llm->complete(
                    AiAssistantPrompts::systemForMode('landing_page'),
                    AiAssistantPrompts::user('landing_page', $ctx),
                    AiAssistantPrompts::maxTokensForMode('landing_page'),
                    $company,
                );
            } else {
                $result = $llm->suggest($data['mode'], $ctx, $company);
            }
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        $credits = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, $data['mode']);

        return response()->json([
            'mode' => $data['mode'],
            'suggestion' => $result['suggestion'],
            'model' => $result['model'],
            'disclaimer' => __('AI-generated content — review before sending to clients.'),
            'ai_credits_charged' => $credits,
            'ai_credits_cost' => $creditCost,
            'input_tokens' => (int) ($result['input_tokens'] ?? 0),
            'output_tokens' => (int) ($result['output_tokens'] ?? 0),
            'total_tokens' => (int) ($result['total_tokens'] ?? 0),
        ]);
    }

    public function speak(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        NovaTextToSpeechService $tts,
    ) {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'nova_voice');

        if (! $planLimits->isFeatureEnabled($company, 'ai_credits')) {
            return $this->speakErrorResponse(__('AI credits are not available on your plan.'), 'plan_disabled', 403);
        }

        if (! $planLimits->allows($company, 'ai_credits', $creditCost)) {
            return $this->speakErrorResponse(
                $this->aiCreditsLimitMessage($planLimits, $company, $creditCost),
                'ai_credits_limit',
                403,
            );
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        if (! $tts->isConfigured($company)) {
            return $this->speakErrorResponse(
                __('Configure a voice provider in platform settings to enable Nova voice.'),
                'tts_unconfigured',
                503,
            );
        }

        try {
            $result = $tts->synthesize($data['text'], app()->getLocale(), $company);
        } catch (RuntimeException $e) {
            return $this->speakErrorResponse($e->getMessage(), 'tts_failed', 503);
        }

        $charged = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, 'nova_voice');

        return response($result['binary'], 200, [
            'Content-Type' => $result['mime'],
            'Cache-Control' => 'no-store, private',
            'X-AI-Credits-Charged' => (string) $charged,
            'X-AI-Credits-Cost' => (string) $creditCost,
            'X-Nova-TTS-Provider' => $result['provider'],
        ]);
    }

    public function briefing(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        NovaVoiceBriefingService $briefing,
        NovaTextToSpeechService $tts,
    ) {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $user = $request->user();
        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'nova_briefing');

        if (! $planLimits->isFeatureEnabled($company, 'ai_credits')) {
            return response()->json(['message' => __('AI credits are not available on your plan.')], 403);
        }

        if (! $planLimits->allows($company, 'ai_credits', $creditCost)) {
            return response()->json([
                'message' => $this->aiCreditsLimitMessage($planLimits, $company, $creditCost),
            ], 403);
        }

        $cacheKey = 'nova_briefing_text:'.$user->id;
        $textOnly = $request->boolean('text_only');
        $replay = $request->boolean('replay');

        if ($textOnly && $replay) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return response()->json([
                    'text' => $cached,
                    'replay' => true,
                ]);
            }
        }

        $built = $briefing->buildBriefing($company, $user);
        Cache::put($cacheKey, $built['text'], now()->addMinutes(5));

        if ($textOnly) {
            $charged = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, 'nova_briefing');

            return response()->json([
                'text' => $built['text'],
            ])->withHeaders([
                'X-AI-Credits-Charged' => (string) $charged,
                'X-AI-Credits-Cost' => (string) $creditCost,
            ]);
        }

        if (! $tts->isConfigured($company)) {
            return response()->json([
                'message' => __('Configure a voice provider in platform settings to enable Nova voice.'),
                'fallback' => 'browser',
                'text' => $built['text'],
            ], 503);
        }

        try {
            $result = $tts->synthesize($built['text'], app()->getLocale(), $company);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'fallback' => 'browser',
                'text' => $built['text'],
            ], 503);
        }

        $charged = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, 'nova_briefing');

        return response($result['binary'], 200, [
            'Content-Type' => $result['mime'],
            'Cache-Control' => 'no-store, private',
            'X-AI-Credits-Charged' => (string) $charged,
            'X-AI-Credits-Cost' => (string) $creditCost,
            'X-Nova-TTS-Provider' => $result['provider'],
        ]);
    }

    public function voiceWorkflow(Request $request, NovaVoiceWorkflowService $workflows): JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'action' => ['required', 'string', 'in:start,advance,cancel'],
            'workflow' => ['nullable', 'string', 'max:64'],
            'input' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            if ($data['action'] === 'cancel') {
                $workflows->clearSession($request->user());
                $result = [
                    'active' => false,
                    'workflow' => null,
                    'reply' => __('nova_workflow_cancelled'),
                    'done' => true,
                    'redirect_url' => null,
                ];
            } elseif ($data['action'] === 'start') {
                $result = $workflows->start($request->user(), (string) ($data['workflow'] ?? ''));
            } else {
                $result = $workflows->advance($request->user(), (string) ($data['input'] ?? ''));
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json($result);
    }

    public function proposalClientContext(Request $request, ProposalWritingContextService $contexts): JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'client_id' => ['nullable', 'string'],
        ]);

        return response()->json([
            'context' => $contexts->contextForClient($company, $data['client_id'] ?? null),
        ]);
    }

    public function proposalPrefill(Request $request): JsonResponse|RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if(! $request->user()->can('workspace.manage_invoices'), 403);

        $data = $request->validate([
            'client_id' => ['nullable', 'string'],
            'outline' => ['required', 'string', 'max:12000'],
            'quote_name' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_major' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['client_id'])) {
            $exists = Client::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('id', $data['client_id'])
                ->exists();
            abort_unless($exists, 422);
        }

        $request->session()->put('assistant_proposal_prefill', [
            'client_id' => $data['client_id'] ?? null,
            'outline' => $data['outline'],
            'quote_name' => $data['quote_name'] ?? null,
            'items' => $data['items'] ?? [],
        ]);

        return response()->json([
            'redirect' => route('proposals.create', ['from_assistant' => 1]),
        ]);
    }

    private function aiCreditsLimitMessage(PlanLimitService $planLimits, $company, int $creditCost): string
    {
        $nav = $planLimits->aiCreditsForNav($company);
        $remaining = $nav['unlimited'] ? null : (int) ($nav['remaining'] ?? 0);

        return __('ai_credits_insufficient', [
            'required' => max(1, $creditCost),
            'remaining' => $remaining ?? __('Unlimited'),
        ]);
    }

    private function speakErrorResponse(string $message, string $code, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'fallback' => 'browser',
            'code' => $code,
        ], $status);
    }
}
