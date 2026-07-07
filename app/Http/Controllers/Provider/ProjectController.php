<?php

namespace App\Http\Controllers\Provider;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $provider = $this->providerOrAbort();
        $projects = Project::query()->withoutGlobalScopes()
            ->where('company_id', $provider->company_id)
            ->where('provider_id', $provider->id)
            ->with('client')
            ->latest()
            ->paginate(15);

        return view('provider.projects.index', compact('projects', 'provider'));
    }

    public function create(): View
    {
        $provider = $this->providerOrAbort();
        $provider->loadMissing('company');
        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $provider->company_id)->orderBy('name')->get();

        return view('provider.projects.create', compact('provider', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = $this->providerOrAbort();

        $emptyMinor = [];
        if ($request->has('negotiated_price') && $request->input('negotiated_price') === '') {
            $emptyMinor['negotiated_price'] = null;
        }
        if ($emptyMinor !== []) {
            $request->merge($emptyMinor);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $provider->company_id))],
            'description' => ['nullable', 'string'],
            'negotiated_price' => ['nullable', 'numeric', 'min:0'],
            'final_deadline' => ['nullable', 'date'],
        ]);

        $cur = strtoupper((string) (Company::query()->whereKey($provider->company_id)->value('default_currency') ?? 'USD'));
        $negoMinor = null;
        if (isset($data['negotiated_price']) && $data['negotiated_price'] !== null && $data['negotiated_price'] !== '') {
            $negoMinor = flowdesk_decimal_to_minor((string) $data['negotiated_price'], $cur);
        }

        $project = Project::query()->withoutGlobalScopes()->create([
            'company_id' => $provider->company_id,
            'client_id' => $data['client_id'] ?? null,
            'provider_id' => $provider->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
            'negotiated_price' => $negoMinor,
            'final_deadline' => $data['final_deadline'] ?? null,
            'source' => ProjectSource::BusinessProvider,
        ]);

        return redirect()->route('provider.projects.show', $project)->with('status', __('Project created.'));
    }

    public function show(Project $project): View
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);
        $project->load(['client', 'proposals' => fn ($q) => $q->latest()]);

        return view('provider.projects.show', compact('project', 'provider'));
    }

    public function edit(Project $project): View
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);
        $project->loadMissing('company');
        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $provider->company_id)->orderBy('name')->get();

        return view('provider.projects.edit', compact('project', 'provider', 'clients'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);

        $emptyMinor = [];
        if ($request->has('negotiated_price') && $request->input('negotiated_price') === '') {
            $emptyMinor['negotiated_price'] = null;
        }
        if ($emptyMinor !== []) {
            $request->merge($emptyMinor);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $provider->company_id))],
            'description' => ['nullable', 'string'],
            'negotiated_price' => ['nullable', 'numeric', 'min:0'],
            'final_deadline' => ['nullable', 'date'],
        ]);

        $cur = strtoupper((string) (Company::query()->whereKey($provider->company_id)->value('default_currency') ?? 'USD'));
        $negoMinor = null;
        if (isset($data['negotiated_price']) && $data['negotiated_price'] !== null && $data['negotiated_price'] !== '') {
            $negoMinor = flowdesk_decimal_to_minor((string) $data['negotiated_price'], $cur);
        }

        $project->update([
            'title' => $data['title'],
            'status' => $data['status'],
            'client_id' => $data['client_id'] ?? null,
            'description' => $data['description'] ?? null,
            'negotiated_price' => $negoMinor,
            'final_deadline' => $data['final_deadline'] ?? null,
        ]);

        return redirect()->route('provider.projects.show', $project)->with('status', __('Project updated.'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $provider = $this->providerOrAbort();
        $this->authorizeProject($project, $provider);
        $project->delete();

        return redirect()->route('provider.projects.index')->with('status', __('Project removed.'));
    }

    private function providerOrAbort(): Provider
    {
        $user = auth()->user();
        abort_if(! $user || ! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        abort_if(! $provider, 403);

        return $provider;
    }

    private function authorizeProject(Project $project, Provider $provider): void
    {
        abort_if((string) $project->company_id !== (string) $provider->company_id, 403);
        abort_if((string) $project->provider_id !== (string) $provider->id, 403);
    }
}
