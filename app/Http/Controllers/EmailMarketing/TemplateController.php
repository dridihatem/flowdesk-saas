<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingTemplate;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use App\Support\EmailMarketingTemplateLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! $request->user()->company, 403);

        $modelTemplates = EmailMarketingTemplateLibrary::models();

        $category = $request->query('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        $categoryOptions = EmailMarketingTemplate::query()
            ->where('company_id', (string) $request->user()->company_id)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->pluck('category')
            ->unique()
            ->values()
            ->all();

        $q = EmailMarketingTemplate::query()
            ->orderBy('category')
            ->orderBy('name');
        if ($category !== null) {
            $q->where('category', $category);
        }
        $templates = $q->paginate(15)->withQueryString();

        $importedModelKeys = EmailMarketingTemplate::query()
            ->whereNotNull('source_model_key')
            ->pluck('source_model_key')
            ->all();

        return view('email-marketing.templates.index', compact(
            'templates',
            'modelTemplates',
            'importedModelKeys',
            'categoryOptions',
            'category',
        ));
    }

    public function create(Request $request, PlatformLlmRouter $llm, PlanLimitService $planLimits): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $aiAvailable = $llm->isAvailable($company) && $planLimits->isFeatureEnabled($company, 'ai_credits');

        return view('email-marketing.templates.create', compact('aiAvailable'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedWorkspaceTemplate($request);

        EmailMarketingTemplate::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'category' => $data['category'],
            'body_html' => $data['body_html'],
            'source_model_key' => null,
        ]);

        return redirect()
            ->route('email-marketing.templates.index')
            ->with('status', __('email_marketing_workspace_template_saved'));
    }

    public function edit(Request $request, EmailMarketingTemplate $template, PlatformLlmRouter $llm, PlanLimitService $planLimits): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $this->authorizeWorkspaceTemplate($request, $template);
        $aiAvailable = $llm->isAvailable($company) && $planLimits->isFeatureEnabled($company, 'ai_credits');

        return view('email-marketing.templates.edit', compact('template', 'aiAvailable'));
    }

    public function update(Request $request, EmailMarketingTemplate $template): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeWorkspaceTemplate($request, $template);

        $data = $this->validatedWorkspaceTemplate($request);

        $template->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'body_html' => $data['body_html'],
        ]);

        return redirect()
            ->route('email-marketing.templates.index')
            ->with('status', __('email_marketing_workspace_template_saved'));
    }

    public function destroy(Request $request, EmailMarketingTemplate $template): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeWorkspaceTemplate($request, $template);

        $template->delete();

        return redirect()
            ->route('email-marketing.templates.index')
            ->with('status', __('email_marketing_workspace_template_deleted'));
    }

    public function storeFromModel(Request $request, string $slug): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $definition = EmailMarketingTemplateLibrary::model($slug);
        abort_if($definition === null, 404);

        $exists = EmailMarketingTemplate::query()
            ->where('source_model_key', $slug)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('email-marketing.templates.index')
                ->with('status', __('email_marketing_template_model_already_added'));
        }

        $category = $definition['category'] ?? null;
        $category = is_string($category) && $category !== '' ? $category : null;

        EmailMarketingTemplate::query()->create([
            'company_id' => $company->id,
            'name' => $definition['name'],
            'category' => $category,
            'body_html' => $definition['body_html'],
            'source_model_key' => $slug,
        ]);

        return redirect()
            ->route('email-marketing.templates.index')
            ->with('status', __('email_marketing_template_model_added'));
    }

    private function authorizeWorkspaceTemplate(Request $request, EmailMarketingTemplate $template): void
    {
        $companyId = $request->user()?->company_id;
        abort_if(! $companyId || (string) $template->company_id !== (string) $companyId, 403);
    }

    /**
     * @return array{name: string, category: string|null, body_html: string}
     */
    private function validatedWorkspaceTemplate(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'body_html' => ['required', 'string'],
        ]);

        $data['category'] = isset($data['category']) && $data['category'] !== ''
            ? $data['category']
            : null;

        return $data;
    }
}
