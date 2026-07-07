<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingTemplateModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailMarketingTemplateModelController extends Controller
{
    public function index(): View
    {
        $models = EmailMarketingTemplateModel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.email-template-models.index', compact('models'));
    }

    public function create(): View
    {
        return view('admin.email-template-models.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        EmailMarketingTemplateModel::query()->create($data);

        return redirect()
            ->route('admin.email-template-models.index')
            ->with('status', __('admin_email_template_model_saved'));
    }

    public function edit(EmailMarketingTemplateModel $emailMarketingTemplateModel): View
    {
        return view('admin.email-template-models.edit', ['model' => $emailMarketingTemplateModel]);
    }

    public function update(Request $request, EmailMarketingTemplateModel $emailMarketingTemplateModel): RedirectResponse
    {
        $data = $this->validated($request, $emailMarketingTemplateModel->id);

        $emailMarketingTemplateModel->update($data);

        return redirect()
            ->route('admin.email-template-models.index')
            ->with('status', __('admin_email_template_model_saved'));
    }

    public function destroy(EmailMarketingTemplateModel $emailMarketingTemplateModel): RedirectResponse
    {
        $emailMarketingTemplateModel->delete();

        return redirect()
            ->route('admin.email-template-models.index')
            ->with('status', __('admin_email_template_model_deleted'));
    }

    /**
     * @return array{slug: string, name: string, category: string|null, body_html: string, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?string $ignoreId = null): array
    {
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('email_marketing_template_models', 'slug')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'body_html' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['category'] = $data['category'] !== null && $data['category'] !== '' ? $data['category'] : null;

        return $data;
    }
}
