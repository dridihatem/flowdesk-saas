<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Api\Concerns\ResolvesApiWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectApiController extends Controller
{
    use ResolvesApiWorkspace;

    public function index(Request $request): JsonResponse
    {
        $company = $this->apiCompany();
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $query = Project::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('client:id,name')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->string('client_id')->toString());
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Project $p) => $this->projectPayload($p))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        $company = $this->apiCompany();
        abort_if((string) $project->company_id !== (string) $company->id, 404);
        $project->load('client:id,name');

        return response()->json(['data' => $this->projectPayload($project)]);
    }

    public function store(Request $request, PlanLimitService $planLimits): JsonResponse
    {
        $company = $this->apiCompany();
        $planLimits->assertAllows($company, 'projects');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'status' => ['nullable', 'string', Rule::enum(ProjectStatus::class)],
            'source' => ['nullable', 'string', Rule::enum(ProjectSource::class)],
            'final_deadline' => ['nullable', 'date'],
        ]);

        if (! empty($data['client_id'])) {
            $client = Client::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereKey($data['client_id'])
                ->first();
            abort_if(! $client, 422, __('Client not found in this workspace.'));
        }

        $project = Project::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'client_id' => $data['client_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? ProjectStatus::Draft,
            'source' => $data['source'] ?? ProjectSource::Internal,
            'final_deadline' => $data['final_deadline'] ?? null,
            'created_by' => null,
        ]);

        $project->load('client:id,name');

        return response()->json(['data' => $this->projectPayload($project)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectPayload(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status?->value,
            'source' => $project->source?->value,
            'client_id' => $project->client_id,
            'client_name' => $project->client?->name,
            'final_deadline' => $project->final_deadline?->format('Y-m-d'),
            'created_at' => $project->created_at?->toIso8601String(),
        ];
    }
}
