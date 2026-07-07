<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AiCreditUsageService;
use App\Services\PlanLimitService;
use App\Services\ProjectExampleWorkspaceAiService;
use App\Services\ProjectWorkflowAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProjectAiController extends Controller
{
    public function generateWorkflow(
        Request $request,
        Project $project,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        ProjectWorkflowAiService $workflow,
    ): RedirectResponse {
        $this->authorizeProject($project);
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_PROJECT_WORKFLOW);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        try {
            [$tasksCreated, $llmResult] = $workflow->generateDescriptionAndTasks($project);
        } catch (RuntimeException $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        }

        $usage->recordForTask($company, AiCreditUsageService::TASK_PROJECT_WORKFLOW);

        if ($tasksCreated === 0) {
            return back()->with('status', __('Description saved. No tasks were suggested — you can edit the description and try again.'));
        }

        return back()->with('status', $tasksCreated === 1
            ? __('Description saved and one task was added.')
            : __('Description saved and :count tasks were added.', ['count' => $tasksCreated]));
    }

    public function createExampleWorkspace(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        ProjectExampleWorkspaceAiService $example,
    ): RedirectResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_PROJECT_EXAMPLE);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        $used = Project::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $planLimit = $planLimits->planLimitValue($company, 'projects');
        $maxAllowed = $planLimit === null
            ? ProjectExampleWorkspaceAiService::MAX_PROJECTS_PER_RUN
            : max(0, (int) $planLimit - (int) $used);

        if ($maxAllowed < 1) {
            return redirect()
                ->route('projects.index')
                ->withErrors(['ai' => __('projects_ai_example_not_enough_slots')])
                ->withInput();
        }

        $request->validate([
            'prompt' => ['required', 'string', 'min:20', 'max:8000'],
        ], [
            'prompt.required' => __('projects_ai_prompt_required'),
            'prompt.min' => __('projects_ai_prompt_min'),
        ]);

        $prompt = (string) $request->input('prompt');

        try {
            $out = $example->createProjectsFromPrompt(
                $company,
                $request->user(),
                $prompt,
                $maxAllowed,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('projects.index')
                ->withErrors(['ai' => $e->getMessage()])
                ->withInput();
        }

        $usage->recordForTask($company, AiCreditUsageService::TASK_PROJECT_EXAMPLE);

        $created = $out['projects'];
        $titles = $created->pluck('title')->implode(' · ');

        return redirect()
            ->route('projects.index')
            ->with('status', __('projects_ai_example_created', ['titles' => $titles]));
    }

    private function authorizeProject(Project $project): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $project->company_id !== (string) $company->id, 403);
    }
}
