<?php

namespace App\Http\Controllers;

use App\Models\Form as LeadForm;
use App\Models\FormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormFieldController extends Controller
{
    private const ALLOWED_TYPES = [
        'text', 'email', 'textarea', 'number', 'tel',
        'radio', 'checkbox', 'select', 'file', 'date', 'url',
        'heading', 'paragraph',
    ];

    private const OPTION_TYPES = ['radio', 'checkbox', 'select'];

    private const DECORATIVE_TYPES = ['heading', 'paragraph'];

    public function store(Request $request, LeadForm $form): RedirectResponse
    {
        $this->authorizeForm($form);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'required' => ['sometimes', 'boolean'],
            'step' => ['nullable', 'integer', 'min:0', 'max:50'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'string', 'max:2000'],
        ]);

        $meta = [];
        if (! empty($data['placeholder'])) {
            $meta['placeholder'] = $data['placeholder'];
        }
        if (in_array($data['type'], self::OPTION_TYPES, true) && ! empty($data['options'])) {
            $meta['options'] = array_values(array_filter(
                array_map('trim', explode("\n", $data['options'])),
                fn ($v) => $v !== '',
            ));
        }

        $maxOrder = (int) $form->fields()->max('sort_order');

        FormField::query()->withoutGlobalScopes()->create([
            'company_id' => $form->company_id,
            'form_id' => $form->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'sort_order' => $maxOrder + 1,
            'required' => in_array($data['type'], self::DECORATIVE_TYPES, true) ? false : $request->boolean('required'),
            'step' => $data['step'] ?? 0,
            'meta' => $meta ?: null,
        ]);

        return redirect()->route('forms.edit', $form)->with('status', __('Field added.'));
    }

    public function destroy(LeadForm $form, FormField $field): RedirectResponse
    {
        $this->authorizeForm($form);
        abort_if((string) $field->form_id !== (string) $form->id, 404);

        $field->delete();

        return redirect()->route('forms.edit', $form)->with('status', __('Field removed.'));
    }

    public function reorder(Request $request, LeadForm $form): RedirectResponse
    {
        $this->authorizeForm($form);

        $order = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct'],
        ])['order'];

        foreach ($order as $index => $fieldId) {
            $updated = FormField::query()->withoutGlobalScopes()
                ->where('form_id', $form->id)
                ->where('id', $fieldId)
                ->update(['sort_order' => $index]);
            abort_if($updated === 0, 404);
        }

        return redirect()->route('forms.edit', $form)->with('status', __('Field order saved.'));
    }

    public function update(Request $request, LeadForm $form, FormField $field): RedirectResponse
    {
        $this->authorizeForm($form);
        abort_if((string) $field->form_id !== (string) $form->id, 404);

        $data = $request->validate([
            'step' => ['nullable', 'integer', 'min:0', 'max:50'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->has('required')) {
            $field->required = in_array($field->type, self::DECORATIVE_TYPES, true) ? false : $request->boolean('required');
        }
        if (array_key_exists('step', $data) && $data['step'] !== null) {
            $field->step = (int) $data['step'];
        }

        $meta = $field->meta ?? [];
        if ($request->has('placeholder')) {
            $meta['placeholder'] = $data['placeholder'] ?? '';
        }
        if ($request->has('options') && in_array($field->type, self::OPTION_TYPES, true)) {
            $meta['options'] = array_values(array_filter(
                array_map('trim', explode("\n", $data['options'] ?? '')),
                fn ($v) => $v !== '',
            ));
        }
        $field->meta = $meta ?: null;
        $field->save();

        return redirect()->route('forms.edit', $form)->with('status', __('Field updated.'));
    }

    private function authorizeForm(LeadForm $form): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $form->company_id !== (string) $company->id, 403);
    }
}
