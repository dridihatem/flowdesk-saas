<?php

namespace App\Http\Controllers\Nova;

use App\AI\Nova\Agent\NovaAgent;
use App\AI\Nova\Autopilot\AutopilotGate;
use App\AI\Nova\Memory\ContextBuilder;
use App\AI\Nova\Project\PlanPreviewService;
use App\Models\Nova\NovaActivity;
use App\Models\Nova\NovaRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NovaAgentController
{
    public function run(Request $request, NovaAgent $agent): JsonResponse
    {
        $user = $request->user();
        $company = $user?->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'string'],
            'current_page' => ['nullable', 'string', 'max:255'],
            'current_entity' => ['nullable', 'array'],
            'current_entity.type' => ['nullable', 'string', 'max:64'],
            'current_entity.id' => ['nullable', 'string', 'max:64'],
            'confirmation' => ['nullable', 'array'],
            'confirmation.confirmed' => ['nullable', 'boolean'],
            'confirmation.tool' => ['nullable', 'string'],
            'confirmation.arguments' => ['nullable', 'array'],
        ]);

        $pageContext = [
            'page' => $data['current_page'] ?? null,
            'entity' => $data['current_entity'] ?? null,
        ];

        $response = $agent->run(
            $user,
            $company,
            $data['message'],
            $data['conversation_id'] ?? null,
            $pageContext,
            $data['confirmation'] ?? null,
            $request,
        );

        return response()->json($response->toArray());
    }

    public function confirmPlan(Request $request, PlanPreviewService $plans, ContextBuilder $contexts): JsonResponse
    {
        $user = $request->user();
        $company = $user?->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'plan' => ['required', 'array'],
            'plan.confirmed' => ['required', 'accepted'],
        ]);

        $context = $contexts->build($user, $company);
        $result = $plans->createFromConfirmedPlan($data['plan'] + ['confirmed' => true], $context);

        return response()->json(['status' => 'created', 'result' => $result]);
    }

    public function activities(Request $request, string $runId): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user?->company_id, 403);

        $run = NovaRun::query()
            ->withoutGlobalScopes()
            ->whereKey($runId)
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $activities = NovaActivity::query()
            ->where('run_id', $run->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (NovaActivity $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'status' => $a->status,
                'message' => $a->message,
                'tool_name' => $a->tool_name,
            ]);

        return response()->json([
            'run_id' => $run->id,
            'status' => $run->status,
            'activities' => $activities,
        ]);
    }

    public function autopilotStatus(Request $request, AutopilotGate $gate, ContextBuilder $contexts): JsonResponse
    {
        $user = $request->user();
        $company = $user?->company;
        abort_if(! $company, 403);

        return response()->json($gate->status($contexts->build($user, $company)));
    }
}
