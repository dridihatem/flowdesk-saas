<?php

namespace App\AI\Nova\Agent;

use App\AI\Nova\Activities\ActivityManager;
use App\AI\Nova\Autopilot\AutopilotGate;
use App\AI\Nova\Memory\ConversationMemory;
use App\AI\Nova\Memory\ContextBuilder;
use App\AI\Nova\Support\ActionRisk;
use App\AI\Nova\Support\NovaAuditLogger;
use App\AI\Nova\Support\NovaSecurity;
use App\AI\Nova\Tools\ToolRegistry;
use App\Models\Company;
use App\Models\Nova\NovaConversation;
use App\Models\Nova\NovaMessage;
use App\Models\Nova\NovaRun;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class NovaOrchestrator
{
    public function __construct(
        private ContextBuilder $contexts,
        private ConversationMemory $memory,
        private NovaPlanner $planner,
        private ToolRegistry $tools,
        private ActivityManager $activities,
        private NovaSecurity $security,
        private NovaAuditLogger $audit,
        private AutopilotGate $autopilot,
    ) {}

    /**
     * @param  array{page?: string|null, entity?: array{type: string, id: string}|null}|null  $pageContext
     * @param  array<string, mixed>|null  $confirmation
     */
    public function handle(
        User $user,
        Company $company,
        string $input,
        ?string $conversationId = null,
        ?array $pageContext = null,
        ?array $confirmation = null,
        ?Request $request = null,
    ): NovaResponse {
        $this->security->assertActor($user, $company);

        $conversation = $this->resolveConversation($company, $user, $conversationId, $input);
        $context = $this->contexts->build($user, $company, $conversation, $pageContext, $request);
        $this->security->assertContext($context);

        $run = NovaRun::query()->create([
            'conversation_id' => $conversation->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'input' => $input,
            'status' => NovaRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $this->memory->append($conversation, NovaMessage::ROLE_USER, $input);
        $this->activities->record($run, 'thinking', 'Understanding your request…');

        try {
            if (is_array($confirmation) && ($confirmation['confirmed'] ?? false) === true) {
                return $this->executeConfirmed($run, $context, $conversation, $confirmation);
            }

            $decision = $this->planner->decide($input, $context);

            if ($decision->type === 'clarify') {
                return $this->finishClarify($run, $conversation, $decision);
            }

            if ($decision->type === 'respond') {
                return $this->finishRespond($run, $conversation, (string) $decision->message);
            }

            return $this->executeToolPlan($run, $context, $conversation, $decision);
        } catch (Throwable $e) {
            $run->update([
                'status' => NovaRun::STATUS_FAILED,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            $this->activities->record($run, 'error', 'Something went wrong while working on that.');

            return new NovaResponse(
                status: NovaRun::STATUS_FAILED,
                message: 'I hit an error: '.$e->getMessage(),
                runId: $run->id,
                conversationId: $conversation->id,
                activities: $this->activities->timeline($run->fresh()),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $confirmation
     */
    private function executeConfirmed(NovaRun $run, NovaContext $context, NovaConversation $conversation, array $confirmation): NovaResponse
    {
        $toolName = (string) ($confirmation['tool'] ?? '');
        $arguments = $this->security->sanitizeArguments(is_array($confirmation['arguments'] ?? null) ? $confirmation['arguments'] : []);
        $tool = $this->tools->get($toolName);
        if ($tool === null || ! isset($this->tools->forContext($context)[$toolName])) {
            throw new \RuntimeException('Confirmed tool is not available.');
        }

        $this->activities->record($run, 'updating', "Confirming {$toolName}…", $toolName);
        $result = $tool->execute($arguments, $context);
        $risk = ActionRisk::forTool($toolName);
        $this->audit->logTool($context, $run, $toolName, $arguments, $result, $risk, true);

        $message = $this->summarizeResults([$toolName => $result]);
        $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $message);
        $run->update([
            'status' => NovaRun::STATUS_COMPLETED,
            'result' => ['tools' => [$toolName => $result]],
            'completed_at' => now(),
        ]);
        $this->activities->record($run, 'completed', 'Done.');

        return new NovaResponse(
            status: NovaRun::STATUS_COMPLETED,
            message: $message,
            runId: $run->id,
            conversationId: $conversation->id,
            activities: $this->activities->timeline($run->fresh()),
            data: [$toolName => $result],
        );
    }

    private function executeToolPlan(NovaRun $run, NovaContext $context, NovaConversation $conversation, NovaDecision $decision): NovaResponse
    {
        $results = [];
        $assignmentPreview = null;
        $projectPreview = null;

        foreach ($decision->toolCalls as $call) {
            $toolName = (string) ($call['tool'] ?? '');
            $arguments = $this->security->sanitizeArguments(is_array($call['arguments'] ?? null) ? $call['arguments'] : []);
            $tool = $this->tools->get($toolName);
            if ($tool === null || ! isset($this->tools->forContext($context)[$toolName])) {
                continue;
            }

            // Inject client_id from prior search when multi-step.
            if ($toolName === 'invoices.search' && empty($arguments['client_id']) && isset($results['clients.search']['clients'][0]['id'])) {
                $clientCount = (int) ($results['clients.search']['count'] ?? 0);
                if ($clientCount === 1) {
                    $arguments['client_id'] = $results['clients.search']['clients'][0]['id'];
                }
            }
            if ($toolName === 'projects.search' && empty($arguments['client_id']) && isset($results['clients.search']['clients'][0]['id'])) {
                if ((int) ($results['clients.search']['count'] ?? 0) === 1) {
                    $arguments['client_id'] = $results['clients.search']['clients'][0]['id'];
                }
            }

            $risk = ActionRisk::forTool($toolName);
            if (ActionRisk::requiresConfirmation($risk, $context->allowAutoSendInvoices, $toolName)) {
                $msg = "I need your confirmation before I run {$toolName} (risk: {$risk}).";
                $this->activities->record($run, 'waiting', $msg, $toolName);
                $run->update([
                    'status' => NovaRun::STATUS_WAITING_CONFIRMATION,
                    'result' => [
                        'pending' => ['tool' => $toolName, 'arguments' => $arguments, 'risk' => $risk],
                        'partial' => $results,
                    ],
                ]);
                $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $msg);

                return new NovaResponse(
                    status: NovaRun::STATUS_WAITING_CONFIRMATION,
                    message: $msg,
                    runId: $run->id,
                    conversationId: $conversation->id,
                    activities: $this->activities->timeline($run->fresh()),
                    confirmation: [
                        'tool' => $toolName,
                        'arguments' => $arguments,
                        'risk' => $risk,
                        'confirm_required' => true,
                    ],
                    data: $results,
                );
            }

            // assignment_mode=ask gates assign tool
            if ($toolName === 'tasks.assign' && $context->assignmentMode === 'ask') {
                $msg = 'Confirm before I assign this task.';
                $run->update([
                    'status' => NovaRun::STATUS_WAITING_CONFIRMATION,
                    'result' => ['pending' => ['tool' => $toolName, 'arguments' => $arguments, 'risk' => $risk]],
                ]);
                $this->activities->record($run, 'waiting', $msg, $toolName);

                return new NovaResponse(
                    status: NovaRun::STATUS_WAITING_CONFIRMATION,
                    message: $msg,
                    runId: $run->id,
                    conversationId: $conversation->id,
                    activities: $this->activities->timeline($run->fresh()),
                    confirmation: ['tool' => $toolName, 'arguments' => $arguments, 'risk' => $risk],
                );
            }

            $this->activities->record($run, $this->activityTypeForTool($toolName), $this->activityMessageForTool($toolName), $toolName);
            $result = $tool->execute($arguments, $context);
            $results[$toolName] = $result;
            $this->audit->logTool($context, $run, $toolName, $arguments, $result, $risk, false);

            if ($toolName === 'clients.search' && ($result['ambiguous'] ?? false) === true && ($result['count'] ?? 0) > 1) {
                $options = [];
                foreach ($result['clients'] as $i => $client) {
                    $options[] = ['id' => (string) $client['id'], 'label' => ($i + 1).'. '.$client['name']];
                }
                $msg = 'I found '.count($options).' clients matching your request. Which one do you mean?';
                $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $msg);
                $run->update([
                    'status' => NovaRun::STATUS_COMPLETED,
                    'result' => ['clarify' => true, 'tools' => $results],
                    'completed_at' => now(),
                ]);
                $this->activities->record($run, 'completed', 'Waiting for your choice.');

                return new NovaResponse(
                    status: 'clarify',
                    message: $msg,
                    runId: $run->id,
                    conversationId: $conversation->id,
                    activities: $this->activities->timeline($run->fresh()),
                    clarificationOptions: $options,
                    data: $results,
                );
            }

            if ($toolName === 'assignments.recommend') {
                $assignmentPreview = $result;
            }
            if ($toolName === 'documents.analyze' && isset($result['project_plan_preview'])) {
                $projectPreview = $result['project_plan_preview'];
            }
        }

        // Autopilot never silently continues destructive pipelines.
        $this->autopilot->status($context);

        $message = $this->summarizeResults($results);
        $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $message);
        $run->update([
            'status' => NovaRun::STATUS_COMPLETED,
            'result' => ['tools' => $results],
            'completed_at' => now(),
        ]);
        $this->activities->record($run, 'completed', 'Done.');

        return new NovaResponse(
            status: NovaRun::STATUS_COMPLETED,
            message: $message,
            runId: $run->id,
            conversationId: $conversation->id,
            activities: $this->activities->timeline($run->fresh()),
            projectPreview: $projectPreview,
            assignmentPreview: $assignmentPreview,
            data: $results,
        );
    }

    private function finishClarify(NovaRun $run, NovaConversation $conversation, NovaDecision $decision): NovaResponse
    {
        $message = (string) ($decision->message ?? 'Please clarify.');
        $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $message);
        $run->update([
            'status' => NovaRun::STATUS_COMPLETED,
            'result' => ['clarify' => true],
            'completed_at' => now(),
        ]);
        $this->activities->record($run, 'completed', 'Need a bit more information.');

        return new NovaResponse(
            status: 'clarify',
            message: $message,
            runId: $run->id,
            conversationId: $conversation->id,
            activities: $this->activities->timeline($run->fresh()),
            clarificationOptions: $decision->clarificationOptions,
        );
    }

    private function finishRespond(NovaRun $run, NovaConversation $conversation, string $message): NovaResponse
    {
        $this->memory->append($conversation, NovaMessage::ROLE_ASSISTANT, $message);
        $run->update([
            'status' => NovaRun::STATUS_COMPLETED,
            'result' => ['message' => $message],
            'completed_at' => now(),
        ]);
        $this->activities->record($run, 'completed', 'Done.');

        return new NovaResponse(
            status: NovaRun::STATUS_COMPLETED,
            message: $message,
            runId: $run->id,
            conversationId: $conversation->id,
            activities: $this->activities->timeline($run->fresh()),
        );
    }

    private function resolveConversation(Company $company, User $user, ?string $conversationId, string $input): NovaConversation
    {
        if ($conversationId) {
            $existing = NovaConversation::query()
                ->withoutGlobalScopes()
                ->whereKey($conversationId)
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return NovaConversation::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'title' => mb_substr($input, 0, 80),
            'status' => 'active',
        ]);
    }

    private function activityTypeForTool(string $tool): string
    {
        return match (true) {
            str_contains($tool, 'search') => 'searching',
            str_contains($tool, 'analyze') || str_contains($tool, 'report') => 'analyzing',
            str_contains($tool, 'create') => 'creating',
            str_contains($tool, 'update') || str_contains($tool, 'assign') => 'updating',
            str_contains($tool, 'send') => 'sending',
            str_contains($tool, 'plan') => 'planning',
            default => 'checking',
        };
    }

    private function activityMessageForTool(string $tool): string
    {
        return match ($tool) {
            'clients.search' => "I'm searching for clients…",
            'invoices.search' => "I'm checking invoices…",
            'projects.search' => "I'm searching projects…",
            'tasks.search' => "I'm searching tasks…",
            'documents.analyze' => "I'm analyzing the document…",
            'reports.revenue' => "I'm calculating revenue…",
            'reports.workload' => "I'm analyzing workload…",
            'assignments.recommend' => "I'm evaluating team assignments…",
            default => "I'm working on {$tool}…",
        };
    }

    /**
     * @param  array<string, mixed>  $results
     */
    private function summarizeResults(array $results): string
    {
        if ($results === []) {
            return 'I could not run any tools for that request.';
        }

        $parts = [];
        if (isset($results['clients.search'])) {
            $c = $results['clients.search'];
            $parts[] = 'Found '.(int) ($c['count'] ?? 0).' client(s).';
            foreach (($c['clients'] ?? []) as $client) {
                $parts[] = '- '.$client['name'].(isset($client['email']) ? ' <'.$client['email'].'>' : '');
            }
        }
        if (isset($results['invoices.search'])) {
            $inv = $results['invoices.search'];
            $parts[] = 'Invoices: '.(int) ($inv['count'] ?? 0).' (outstanding total '.(int) ($inv['outstanding_total'] ?? 0).').';
        }
        if (isset($results['projects.search'])) {
            $parts[] = 'Projects: '.(int) ($results['projects.search']['count'] ?? 0).' match(es).';
        }
        if (isset($results['reports.revenue'])) {
            $r = $results['reports.revenue'];
            $parts[] = 'Revenue this month: '.($r['revenue_formatted'] ?? '');
        }
        if (isset($results['reports.invoices'])) {
            $r = $results['reports.invoices'];
            $parts[] = 'Overdue invoices: '.(int) ($r['overdue'] ?? 0).'.';
        }
        if (isset($results['reports.workload'])) {
            $over = count($results['reports.workload']['overloaded'] ?? []);
            $parts[] = $over > 0 ? "{$over} team member(s) look overloaded." : 'No overloaded team members detected.';
        }
        if (isset($results['assignments.recommend']['explanation'])) {
            $parts[] = (string) $results['assignments.recommend']['explanation'];
        }
        if (isset($results['documents.analyze']['project_plan_preview']['preview'])) {
            $p = $results['documents.analyze']['project_plan_preview']['preview'];
            $parts[] = sprintf(
                'Project plan ready for review: %d phases, %d tasks, %d dependencies, %d estimated hours. Confirm before I create anything.',
                (int) ($p['phase_count'] ?? 0),
                (int) ($p['task_count'] ?? 0),
                (int) ($p['dependency_count'] ?? 0),
                (int) ($p['estimated_hours'] ?? 0),
            );
        }

        if ($parts === []) {
            return 'Done. '.json_encode($results, JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $parts);
    }
}
