<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesPortalClient;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use App\Services\ProjectGanttTimelineService;
use App\Services\ProjectTaskCommentMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ResolvesPortalClient;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = $client->projects()
            ->with(['provider'])
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($builder) => $builder->where('status', TaskStatus::Done->value),
            ]);

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', '%'.$q.'%')
                    ->orWhereHas('provider', fn ($provider) => $provider->where('name', 'like', '%'.$q.'%'));
            });
        }

        if (is_string($status) && $status !== '' && in_array($status, array_column(ProjectStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return view('portal.projects.index', compact('client', 'projects', 'q', 'status'));
    }

    public function show(Request $request, Project $project): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProject($client, $project);

        $project->load([
            'provider',
            'company',
            'tasks' => fn ($q) => $q->with(['comments.user'])->orderBy('sort_order'),
            'files',
            'installments',
        ]);

        $doneCount = $project->tasks->filter(fn (ProjectTask $t) => $t->status === TaskStatus::Done)->count();
        $totalTasks = $project->tasks->count();
        $progressPct = $totalTasks > 0 ? (int) round(($doneCount / $totalTasks) * 100) : 0;

        return view('portal.projects.show', compact('client', 'project', 'doneCount', 'totalTasks', 'progressPct'));
    }

    public function kanban(Request $request, Project $project): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProject($client, $project);

        $project->load(['tasks.comments.user']);
        $columns = [];
        foreach (TaskStatus::kanbanOrder() as $status) {
            $columns[$status->value] = $project->tasks
                ->filter(fn (ProjectTask $t) => $t->status === $status)
                ->values();
        }

        return view('portal.projects.kanban', compact('client', 'project', 'columns'));
    }

    public function gantt(Request $request, Project $project, ProjectGanttTimelineService $gantt): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalProject($client, $project);

        $tasks = $project->tasks()->with('comments.user')->get();
        $timeline = $gantt->build($tasks);

        return view('portal.projects.gantt', compact('client', 'project', 'tasks', 'timeline'));
    }

    public function storeTaskComment(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalTask($client, $project, $task);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $comment = ProjectTaskComment::query()->withoutGlobalScopes()->create([
            'company_id' => $project->company_id,
            'project_task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
            'is_client' => true,
        ]);

        app(ProjectTaskCommentMailService::class)->notifyForComment($comment);

        return back()->with('status', __('portal_task_comment_posted'));
    }

    public function confirmPrice(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        abort_if((string) $project->company_id !== (string) $client->company_id, 403);
        abort_if((string) $project->client_id !== (string) $client->id, 403);

        $minor = $project->clientAgreedPriceMinor();
        if ($minor === null || $minor <= 0) {
            return back()->withErrors([
                'price' => __('No agreed price is set for this project yet.'),
            ]);
        }

        if ($project->isClientPriceConfirmed()) {
            return back()->with('status', __('Price was already confirmed.'));
        }

        $project->update(['client_price_confirmed_at' => now()]);

        return back()->with('status', __('Client price confirmed status'));
    }
}
