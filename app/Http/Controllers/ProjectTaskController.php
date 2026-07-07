<?php

namespace App\Http\Controllers;

use App\Enums\ProjectFileCategory;
use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use App\Models\ProjectTaskFile;
use App\Services\ProjectFileStorageService;
use App\Services\ProjectTaskCommentMailService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectTaskController extends Controller
{
    public function kanban(Project $project): View
    {
        $this->authorizeProject($project);
        $project->load(['tasks.files']);
        $columns = [];
        foreach (TaskStatus::kanbanOrder() as $status) {
            $columns[$status->value] = $project->tasks
                ->filter(fn (ProjectTask $t) => $t->status === $status)
                ->values();
        }

        $workspaceMoneyCurrency = $this->defaultTaskCurrency(request());

        return view('projects.tasks.kanban', compact('project', 'columns', 'workspaceMoneyCurrency'));
    }

    public function gantt(Project $project): View
    {
        $this->authorizeProject($project);
        $tasks = $project->tasks()->get();
        $timeline = $this->buildGanttTimeline($tasks);

        return view('projects.tasks.gantt', compact('project', 'tasks', 'timeline'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);
        $data = $this->validatedTask($request, partial: false);
        $data['scope'] = $request->enum('scope', TaskScope::class) ?? TaskScope::Core;
        $data['price_mode'] = $request->enum('price_mode', TaskPriceMode::class) ?? TaskPriceMode::Bundled;
        $statusRaw = $data['status'] ?? TaskStatus::Todo;
        $status = $statusRaw instanceof TaskStatus ? $statusRaw : TaskStatus::from((string) $statusRaw);
        unset($data['status']);
        $this->applyTaskFinancialsOnCreate($request, $data, $this->defaultTaskCurrency($request));
        $maxOrder = (int) $project->tasks()->where('status', $status)->max('sort_order');
        $project->tasks()->create([
            ...$data,
            'status' => $status,
            'company_id' => $project->company_id,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('status', __('Task created.'));
    }

    public function update(Request $request, Project $project, string $task): RedirectResponse|JsonResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);
        $data = $this->validatedTask($request, partial: true);
        if ($request->has('scope')) {
            $data['scope'] = $request->enum('scope', TaskScope::class) ?? TaskScope::Core;
        }
        if ($request->has('price_mode')) {
            $data['price_mode'] = $request->enum('price_mode', TaskPriceMode::class) ?? TaskPriceMode::Bundled;
        }
        $this->applyTaskFinancialsOnUpdate($request, $data, $this->defaultTaskCurrency($request));

        if ($data !== []) {
            $taskModel->update($data);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', __('Task updated.'));
    }

    public function destroy(Project $project, string $task): RedirectResponse
    {
        $this->authorizeProject($project);
        $this->resolveTask($project, $task)->delete();

        return back()->with('status', __('Task deleted.'));
    }

    public function storeFile(Request $request, Project $project, string $task, ProjectFileStorageService $fileStorage): RedirectResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);
        $request->validate([
            'file' => ['required', 'file', $fileStorage->maxFileRule(), $fileStorage->mimeRule()],
            'category' => ['required', Rule::enum(ProjectFileCategory::class)],
        ]);
        $category = $request->enum('category', ProjectFileCategory::class) ?? ProjectFileCategory::Other;
        $fileStorage->storeForTask($project, $taskModel, $request->file('file'), $category);

        return back()->with('status', __('File attached to task.'));
    }

    public function destroyFile(Project $project, string $task, string $file): RedirectResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);
        $fileModel = ProjectTaskFile::query()
            ->where('company_id', $project->company_id)
            ->where('project_task_id', $taskModel->id)
            ->whereKey($file)
            ->firstOrFail();
        $fileModel->delete();

        return back()->with('status', __('File removed from task.'));
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        $statusValues = array_map(fn (TaskStatus $s) => $s->value, TaskStatus::cases());
        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['array'],
            'columns.*.*' => ['required', 'string', Rule::exists('project_tasks', 'id')->where('project_id', $project->id)],
        ]);

        foreach ($validated['columns'] as $statusKey => $ids) {
            if (! in_array($statusKey, $statusValues, true)) {
                return response()->json(['message' => __('Invalid task status.')], 422);
            }
            foreach ($ids as $index => $id) {
                ProjectTask::query()->withoutGlobalScopes()
                    ->where('company_id', $project->company_id)
                    ->where('project_id', $project->id)
                    ->whereKey($id)
                    ->update([
                        'status' => $statusKey,
                        'sort_order' => $index,
                    ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function trackingStart(Project $project, string $task): JsonResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);
        $this->pauseOtherActiveTimers($project, $taskModel->id);
        $taskModel->refresh();

        if ($taskModel->tracking_started_at !== null) {
            return $this->trackingJson($project, $taskModel->fresh());
        }

        $taskModel->update(['tracking_started_at' => now()]);

        return $this->trackingJson($project, $taskModel->fresh());
    }

    public function trackingPause(Project $project, string $task): JsonResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);
        $this->flushRunningSegmentToAccumulated($taskModel);

        return $this->trackingJson($project, $taskModel->fresh());
    }

    public function storeComment(Request $request, Project $project, string $task): RedirectResponse
    {
        $this->authorizeProject($project);
        $taskModel = $this->resolveTask($project, $task);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $comment = ProjectTaskComment::query()->withoutGlobalScopes()->create([
            'company_id' => $project->company_id,
            'project_task_id' => $taskModel->id,
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
            'is_client' => false,
        ]);

        app(ProjectTaskCommentMailService::class)->notifyForComment($comment);

        return back()->with('status', __('task_comment_posted'));
    }

    /**
     * @param  Collection<int, ProjectTask>  $tasks
     * @return array{start: CarbonImmutable, end: CarbonImmutable, rangeDays: int, rows: list<array{task: ProjectTask, start: CarbonImmutable, end: CarbonImmutable, offsetDays: float, spanDays: float, offsetPct: float, spanPct: float}>}
     */
    private function buildGanttTimeline($tasks): array
    {
        $today = CarbonImmutable::today();
        /** @var list<array{task: ProjectTask, start: CarbonImmutable, end: CarbonImmutable}> $rows */
        $rows = [];
        foreach ($tasks as $t) {
            $start = $t->starts_on?->toImmutable() ?? CarbonImmutable::parse($t->created_at)->startOfDay();
            $end = $t->ends_on?->toImmutable() ?? $start->addDays(2);
            if ($end->lessThan($start)) {
                $end = $start;
            }
            $rows[] = ['task' => $t, 'start' => $start, 'end' => $end];
        }

        if ($rows === []) {
            $start = $today;
            $end = $today->addDays(14);

            return [
                'start' => $start,
                'end' => $end,
                'rangeDays' => 15,
                'rows' => [],
            ];
        }

        $min = $today;
        $max = $today->addDays(1);
        foreach ($rows as $r) {
            $min = $min->min($r['start']);
            $max = $max->max($r['end']);
        }
        $max = $max->addDay();
        $rawRange = (int) ($min->diffInDays($max) + 1);
        $rangeDays = max(7, $rawRange);
        $chartEnd = $min->addDays($rangeDays - 1);

        $timelineRows = [];
        foreach ($rows as $r) {
            $offsetDays = (float) $min->diffInDays($r['start']);
            $spanDays = max(1, (float) $r['start']->diffInDays($r['end']) + 1);
            $timelineRows[] = [
                'task' => $r['task'],
                'start' => $r['start'],
                'end' => $r['end'],
                'offsetDays' => $offsetDays,
                'spanDays' => $spanDays,
                'offsetPct' => $rangeDays > 0 ? min(100, ($offsetDays / $rangeDays) * 100) : 0,
                'spanPct' => $rangeDays > 0 ? min(100, ($spanDays / $rangeDays) * 100) : 0,
            ];
        }

        return [
            'start' => $min,
            'end' => $chartEnd,
            'rangeDays' => $rangeDays,
            'rows' => $timelineRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTask(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [Rule::requiredIf(! $partial), 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => [Rule::requiredIf(! $partial), 'nullable', Rule::enum(TaskStatus::class)],
            'scope' => ['nullable', Rule::enum(TaskScope::class)],
            'price_mode' => ['nullable', Rule::enum(TaskPriceMode::class)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['nullable', 'string', 'size:3', flowdesk_currency_rule()],
            'billable' => ['nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyTaskFinancialsOnCreate(Request $request, array &$data, string $defaultCurrency): void
    {
        $amountRaw = $request->input('amount');
        if ($amountRaw !== null && $amountRaw !== '') {
            $cur = $request->input('currency');
            $cur = $cur !== null && $cur !== '' ? strtoupper((string) $cur) : strtoupper($defaultCurrency);
            $data['amount_cents'] = flowdesk_decimal_to_minor((string) $amountRaw, $cur);
            $data['currency'] = $cur;
        } else {
            $data['amount_cents'] = null;
            $data['currency'] = null;
        }
        unset($data['amount']);
        $data['billable'] = $request->boolean('billable', true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyTaskFinancialsOnUpdate(Request $request, array &$data, string $defaultCurrency): void
    {
        if ($request->has('amount')) {
            $amountRaw = $request->input('amount');
            if ($amountRaw === null || $amountRaw === '') {
                $data['amount_cents'] = null;
                $data['currency'] = null;
            } else {
                $cur = $request->input('currency');
                $cur = $cur !== null && $cur !== '' ? strtoupper((string) $cur) : strtoupper($defaultCurrency);
                $data['amount_cents'] = flowdesk_decimal_to_minor((string) $amountRaw, $cur);
                $data['currency'] = $cur;
            }
        }
        if ($request->has('billable')) {
            $data['billable'] = $request->boolean('billable');
        }
        unset($data['amount']);
    }

    private function defaultTaskCurrency(Request $request): string
    {
        $user = $request->user();
        $company = $user?->company;

        return flowdesk_normalize_currency_code($company?->default_currency ?? $user?->default_currency);
    }

    private function resolveTask(Project $project, string $taskId): ProjectTask
    {
        return ProjectTask::query()
            ->where('project_id', $project->id)
            ->whereKey($taskId)
            ->firstOrFail();
    }

    private function pauseOtherActiveTimers(Project $project, string $exceptTaskId): void
    {
        $others = ProjectTask::query()
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->whereKeyNot($exceptTaskId)
            ->whereNotNull('tracking_started_at')
            ->get();

        foreach ($others as $t) {
            $this->flushRunningSegmentToAccumulated($t);
        }
    }

    private function flushRunningSegmentToAccumulated(ProjectTask $task): void
    {
        if ($task->tracking_started_at === null) {
            return;
        }

        $extra = max(0, (int) $task->tracking_started_at->diffInSeconds(now()));
        $task->update([
            'tracking_accumulated_seconds' => (int) $task->tracking_accumulated_seconds + $extra,
            'tracking_started_at' => null,
        ]);
    }

    /**
     * @return array{ok: true, running: bool, started_at: string|null, accumulated_seconds: int, elapsed_seconds: int, project_tasks: list<array{id: string, running: bool, started_at: string|null, accumulated_seconds: int, elapsed_seconds: int}>}
     */
    private function trackingJson(Project $project, ProjectTask $focus): JsonResponse
    {
        $project->refresh()->load('tasks');

        return response()->json([
            ...$this->trackingPayload($focus),
            'project_tasks' => $project->tasks->map(fn (ProjectTask $t): array => [
                'id' => $t->id,
                'running' => $t->tracking_started_at !== null,
                'started_at' => $t->tracking_started_at?->toIso8601String(),
                'accumulated_seconds' => (int) $t->tracking_accumulated_seconds,
                'elapsed_seconds' => $t->elapsedTrackingSeconds(),
            ])->values()->all(),
        ]);
    }

    /**
     * @return array{ok: true, running: bool, started_at: string|null, accumulated_seconds: int, elapsed_seconds: int}
     */
    private function trackingPayload(ProjectTask $task): array
    {
        return [
            'ok' => true,
            'running' => $task->tracking_started_at !== null,
            'started_at' => $task->tracking_started_at?->toIso8601String(),
            'accumulated_seconds' => (int) $task->tracking_accumulated_seconds,
            'elapsed_seconds' => $task->elapsedTrackingSeconds(),
        ];
    }

    private function authorizeProject(Project $project): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $project->company_id !== (string) $company->id, 403);
    }
}
