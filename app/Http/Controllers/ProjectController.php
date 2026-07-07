<?php

namespace App\Http\Controllers;

use App\Enums\ProjectFileCategory;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriceMode;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectTask;
use App\Models\Provider;
use App\Models\User;
use App\Services\AiCreditUsageService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use App\Services\ProjectFileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, PlatformLlmRouter $llm, PlanLimitService $planLimits): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $q = $request->string('q')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $query = Project::query()->withoutGlobalScopes()->where('company_id', $company->id)->latest();

        if ($q !== '') {
            $query->where('title', 'like', '%'.$q.'%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $source = $request->string('source')->trim()->toString();
        if ($source !== '') {
            $query->where('source', $source);
        }

        $baseStats = Project::query()->withoutGlobalScopes()->where('company_id', $company->id);
        $today = today();
        $soonEnd = $today->copy()->addDays(7);
        $projectStats = [
            'completed' => (clone $baseStats)->where('status', ProjectStatus::Completed)->count(),
            'overdue' => (clone $baseStats)
                ->where('status', '!=', ProjectStatus::Completed)
                ->whereNotNull('final_deadline')
                ->whereDate('final_deadline', '<', $today)
                ->count(),
            'due_soon' => (clone $baseStats)
                ->where('status', '!=', ProjectStatus::Completed)
                ->whereNotNull('final_deadline')
                ->whereDate('final_deadline', '>=', $today)
                ->whereDate('final_deadline', '<=', $soonEnd)
                ->count(),
            'pending' => (clone $baseStats)
                ->where('status', '!=', ProjectStatus::Completed)
                ->where(function ($q) use ($soonEnd): void {
                    $q->whereNull('final_deadline')
                        ->orWhereDate('final_deadline', '>', $soonEnd);
                })
                ->count(),
        ];

        $projects = $query
            ->with([
                'client',
                'provider',
                'creator',
                'formSubmission.form',
                'teamMembers',
            ])
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->where('status', TaskStatus::Done->value),
            ])
            ->paginate(15)
            ->withQueryString();

        $usedProjects = (clone $baseStats)->count();
        $projectLimit = $planLimits->planLimitValue($company, 'projects');
        $roomForThree = $projectLimit === null || $usedProjects + 3 <= $projectLimit;
        $aiCredits = app(AiCreditUsageService::class);
        $aiExampleCreditCost = $aiCredits->creditsForTask(AiCreditUsageService::TASK_PROJECT_EXAMPLE);
        $showAiExampleCard = $llm->isAvailable($company)
            && $planLimits->isFeatureEnabled($company, 'ai_credits')
            && $planLimits->allows($company, 'ai_credits', $aiExampleCreditCost)
            && $roomForThree;

        return view('projects.index', compact(
            'projects',
            'q',
            'status',
            'source',
            'projectStats',
            'showAiExampleCard',
            'aiExampleCreditCost',
        ));
    }

    public function create(): View
    {
        $company = auth()->user()->company;
        abort_if(! $company, 403);

        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $providers = Provider::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $teamUsers = User::query()->where('company_id', $company->id)->workspaceStaff()->orderBy('name')->get();

        return view('projects.create', compact('clients', 'providers', 'teamUsers'));
    }

    public function store(Request $request, PlanLimitService $planLimits, ProjectFileStorageService $fileStorage): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $planLimits->assertAllows($company, 'projects');

        $data = $this->validatedProject($request, $company);
        $meta = $request->validate([
            'team_user_ids' => ['nullable', 'array'],
            'team_user_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'attachment' => ['nullable', 'file', $fileStorage->maxFileRule(), $fileStorage->mimeRule()],
            'attachment_category' => ['nullable', Rule::enum(ProjectFileCategory::class)],
        ]);

        $teamIds = $this->validatedWorkspaceTeamIds($meta['team_user_ids'] ?? [], $company->id);

        $project = DB::transaction(function () use ($data, $teamIds, $request, $company, $fileStorage): Project {
            $project = Project::query()->withoutGlobalScopes()->create([
                ...$data,
                'company_id' => $company->id,
                'created_by' => $request->user()->id,
            ]);
            $project->teamMembers()->sync($teamIds);

            if ($request->hasFile('attachment')) {
                $cat = $request->enum('attachment_category', ProjectFileCategory::class) ?? ProjectFileCategory::Other;
                $fileStorage->storeForProject($project, $request->file('attachment'), $cat);
            }

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('status', __('Project created.'));
    }

    public function show(Project $project, PlatformLlmRouter $llm, PlanLimitService $planLimits, ProjectFileStorageService $fileStorage): View
    {
        $this->authorizeProject($project);
        $company = auth()->user()?->company;
        abort_if(! $company, 403);
        $project->load([
            'client',
            'provider',
            'creator',
            'proposals.negotiations',
            'formSubmission.form',
            'teamMembers',
            'files.shares',
            'tasks.files',
            'tasks.comments.user',
            'installments',
        ]);
        $project->loadCount('tasks');
        $project->loadExists('invoices');

        $user = auth()->user();
        $workspaceMoneyCurrency = flowdesk_normalize_currency_code(
            $project->company?->default_currency ?? $user?->company?->default_currency ?? $user?->default_currency,
        );

        /** @var Collection<int, ProjectTask> $tasks */
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();
        $doneTasks = $tasks->where('status', TaskStatus::Done)->count();
        $progressPct = $totalTasks > 0 ? (int) round(100 * $doneTasks / $totalTasks) : 0;
        $overdueCount = $tasks->filter(fn (ProjectTask $t) => $t->isOverdue())->count();
        $additiveBillable = $tasks->filter(function (ProjectTask $t): bool {
            if (! $t->billable) {
                return false;
            }
            $mode = $t->price_mode instanceof TaskPriceMode ? $t->price_mode : TaskPriceMode::tryFrom((string) $t->price_mode);
            if ($mode !== TaskPriceMode::Additive) {
                return false;
            }
            if ($t->amount_cents === null || (int) $t->amount_cents <= 0) {
                return false;
            }

            return true;
        });
        $billableCodes = $additiveBillable->map(fn (ProjectTask $t) => $t->displayCurrency($workspaceMoneyCurrency))->unique()->values();
        $billableTotalFormatted = null;
        if ($billableCodes->count() === 1 && $additiveBillable->isNotEmpty()) {
            $code = $billableCodes->first();
            $sumCents = $additiveBillable->filter(fn (ProjectTask $t) => $t->displayCurrency($workspaceMoneyCurrency) === $code)->sum('amount_cents');
            $billableTotalFormatted = flowdesk_format_minor((int) $sumCents, $code).' '.$code;
        }

        $teamUsers = User::query()->where('company_id', $user->company_id)->workspaceStaff()->orderBy('name')->get();

        $flowdeskBreadcrumbs = [
            ['label' => __('Projects'), 'href' => route('projects.index')],
            ['label' => __($project->title)],
        ];
        $flowdeskBreadcrumbBack = route('projects.index');

        $aiCredits = app(AiCreditUsageService::class);
        $aiWorkflowCreditCost = $aiCredits->creditsForTask(AiCreditUsageService::TASK_PROJECT_WORKFLOW);
        $aiWorkflowAvailable = $llm->isAvailable($company)
            && $planLimits->isFeatureEnabled($company, 'ai_credits')
            && $planLimits->allows($company, 'ai_credits', $aiWorkflowCreditCost);

        $projectFileStorage = [
            'used_bytes' => $fileStorage->projectStorageUsedBytes($project),
            'max_bytes' => $fileStorage->maxStorageBytes(),
            'max_file_kb' => $fileStorage->maxFileKb(),
        ];

        return view('projects.show', compact(
            'project',
            'workspaceMoneyCurrency',
            'totalTasks',
            'doneTasks',
            'progressPct',
            'overdueCount',
            'billableTotalFormatted',
            'teamUsers',
            'flowdeskBreadcrumbs',
            'flowdeskBreadcrumbBack',
            'aiWorkflowAvailable',
            'aiWorkflowCreditCost',
            'projectFileStorage',
        ));
    }

    public function updateTeam(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $validated = $request->validate([
            'team_user_ids' => ['nullable', 'array'],
            'team_user_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
        ]);

        $teamIds = $this->validatedWorkspaceTeamIds($validated['team_user_ids'] ?? [], $company->id);
        $project->teamMembers()->sync($teamIds);

        return back()->with('status', __('Team updated.'));
    }

    public function edit(Project $project): View
    {
        $this->authorizeProject($project);
        $company = auth()->user()->company;
        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $providers = Provider::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $teamUsers = User::query()->where('company_id', $company->id)->workspaceStaff()->orderBy('name')->get();
        $project->load(['company', 'teamMembers', 'tasks' => function ($q): void {
            $q->orderBy('sort_order');
        }]);

        $projectMoneyCurrency = strtoupper((string) ($project->company?->default_currency ?? 'USD'));

        return view('projects.edit', compact('project', 'clients', 'providers', 'teamUsers', 'projectMoneyCurrency'));
    }

    public function storeFile(Request $request, Project $project, ProjectFileStorageService $fileStorage): RedirectResponse
    {
        $this->authorizeProject($project);
        $request->validate([
            'file' => ['required', 'file', $fileStorage->maxFileRule(), $fileStorage->mimeRule()],
            'category' => ['required', Rule::enum(ProjectFileCategory::class)],
            'vault' => ['nullable', 'boolean'],
        ]);

        $vault = $request->boolean('vault');
        if ($vault) {
            abort_unless($request->user()->can('workspace.access_vault'), 403);
        }

        $category = $request->enum('category', ProjectFileCategory::class) ?? ProjectFileCategory::Other;
        $fileStorage->storeForProject($project, $request->file('file'), $category, $vault);

        return back()->with('status', $vault ? __('project_vault_file_added') : __('File added to project.'));
    }

    public function destroyFile(Project $project, string $file): RedirectResponse
    {
        $this->authorizeProject($project);
        $fileModel = ProjectFile::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->tap(function ($q): void {
                // Vault files can only be removed by users allowed to access the vault.
                if (! auth()->user()?->can('workspace.access_vault')) {
                    $q->where('is_vault', false);
                }
            })
            ->whereKey($file)
            ->firstOrFail();
        $fileModel->delete();

        return back()->with('status', __('File removed.'));
    }

    public function update(Request $request, Project $project, ProjectFileStorageService $fileStorage): RedirectResponse
    {
        $this->authorizeProject($project);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedProject($request, $company);
        $meta = $request->validate([
            'team_user_ids' => ['nullable', 'array'],
            'team_user_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'attachment' => ['nullable', 'file', $fileStorage->maxFileRule(), $fileStorage->mimeRule()],
            'attachment_category' => ['nullable', Rule::enum(ProjectFileCategory::class)],
        ]);

        $teamIds = $this->validatedWorkspaceTeamIds($meta['team_user_ids'] ?? [], $company->id);

        DB::transaction(function () use ($project, $data, $teamIds, $request, $fileStorage): void {
            $project->update($data);
            $project->teamMembers()->sync($teamIds);

            if ($request->hasFile('attachment')) {
                $cat = $request->enum('attachment_category', ProjectFileCategory::class) ?? ProjectFileCategory::Other;
                $fileStorage->storeForProject($project, $request->file('attachment'), $cat);
            }
        });

        return redirect()->route('projects.show', $project)->with('status', __('Project updated.'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);
        $project->delete();

        return redirect()->route('projects.index')->with('status', __('Project deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProject(Request $request, Company $company): array
    {
        $companyId = $company->id;
        $clientRule = Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId));
        $providerRule = Rule::exists('providers', 'id')->where(fn ($q) => $q->where('company_id', $companyId));

        $emptyMinor = [];
        foreach (['final_price', 'negotiated_price'] as $key) {
            if ($request->has($key) && $request->input($key) === '') {
                $emptyMinor[$key] = null;
            }
        }
        if ($emptyMinor !== []) {
            $request->merge($emptyMinor);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'source' => ['required', Rule::enum(ProjectSource::class)],
            'client_id' => ['nullable', 'string', $clientRule],
            'provider_id' => ['nullable', 'string', $providerRule],
            'description' => ['nullable', 'string'],
            'final_price' => ['nullable', 'numeric', 'min:0'],
            'negotiated_price' => ['nullable', 'numeric', 'min:0'],
            'final_deadline' => ['nullable', 'date'],
        ]);

        if (empty($data['client_id'])) {
            $data['client_id'] = null;
        }
        if (empty($data['provider_id'])) {
            $data['provider_id'] = null;
        }

        $cur = strtoupper((string) ($company->default_currency ?? 'USD'));
        foreach (['final_price', 'negotiated_price'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if ($v === null || $v === '') {
                $data[$key] = null;

                continue;
            }
            $data[$key] = flowdesk_decimal_to_minor((string) $v, $cur);
        }

        return $data;
    }

    private function authorizeProject(Project $project): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $project->company_id !== (string) $company->id, 403);
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function validatedWorkspaceTeamIds(array $ids, string $companyId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $allowed = User::query()
            ->where('company_id', $companyId)
            ->workspaceStaff()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        return array_values(array_intersect($ids, $allowed));
    }
}
