<?php

namespace App\Http\Controllers;

use App\Models\Form as LeadForm;
use App\Models\WidgetEvent;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $forms = LeadForm::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->withCount(['fields', 'submissions'])
            ->latest()
            ->paginate(15);

        return view('forms.index', compact('forms'));
    }

    public function create(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        return view('forms.create', [
            'baseUrl' => $baseUrl,
            'hasApiToken' => $company->api_token_hash !== null,
            'apiTokenPlain' => $company->apiTokenPlain(),
        ]);
    }

    public function store(Request $request, PlanLimitService $planLimits): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $planLimits->assertAllows($company, 'forms');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published'],
            'layout' => ['nullable', 'string', 'in:simple,wizard'],
        ]);

        $form = LeadForm::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'status' => $data['status'],
            'layout' => $data['layout'] ?? 'simple',
        ]);

        return redirect()->route('forms.edit', $form)->with('status', __('Form created. Add fields below.'));
    }

    public function edit(Request $request, LeadForm $form): View
    {
        $this->authorizeForm($form);
        $form->load(['fields' => fn ($q) => $q->orderBy('sort_order')]);
        $form->loadMissing('company');

        $since = now()->subDays(30);
        $widgetViews = WidgetEvent::query()->withoutGlobalScopes()
            ->where('form_id', $form->id)
            ->where('event', 'view')
            ->where('created_at', '>=', $since)
            ->count();
        $widgetSubmits = WidgetEvent::query()->withoutGlobalScopes()
            ->where('form_id', $form->id)
            ->where('event', 'submit')
            ->where('created_at', '>=', $since)
            ->count();

        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        return view('forms.edit', [
            'form' => $form,
            'widgetViews' => $widgetViews,
            'widgetSubmits' => $widgetSubmits,
            'baseUrl' => $baseUrl,
            'hasApiToken' => $form->company?->api_token_hash !== null,
            'apiTokenPlain' => $form->company?->apiTokenPlain(),
        ]);
    }

    public function update(Request $request, LeadForm $form): RedirectResponse
    {
        $this->authorizeForm($form);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published'],
            'layout' => ['nullable', 'string', 'in:simple,wizard'],
            'widget_primary' => ['nullable', 'string', 'max:32'],
            'widget_theme' => ['nullable', 'string', 'in:light,dark'],
            'captcha_enabled' => ['sometimes', 'boolean'],
        ]);

        $meta = $form->meta ?? [];
        $meta['widget'] = [
            'primary' => $request->input('widget_primary', $meta['widget']['primary'] ?? '#4f46e5'),
            'theme' => $request->input('widget_theme', $meta['widget']['theme'] ?? 'light'),
        ];
        $meta['captcha'] = [
            'enabled' => $request->boolean('captcha_enabled'),
        ];

        $form->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'layout' => $data['layout'] ?? $form->layout ?? 'simple',
            'meta' => $meta,
        ]);

        return redirect()->route('forms.edit', $form)->with('status', __('Form saved.'));
    }

    public function bumpVersion(LeadForm $form): RedirectResponse
    {
        $this->authorizeForm($form);
        $form->increment('widget_version');

        return redirect()->route('forms.edit', $form)->with('status', __('Widget version bumped. Update embeds if needed.'));
    }

    public function destroy(LeadForm $form): RedirectResponse
    {
        $this->authorizeForm($form);
        $form->delete();

        return redirect()->route('forms.index')->with('status', __('Form deleted.'));
    }

    private function authorizeForm(LeadForm $form): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $form->company_id !== (string) $company->id, 403);
    }
}
