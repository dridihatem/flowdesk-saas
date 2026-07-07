<x-admin-layout :title="__('admin_email_template_model_edit')">
    <div class="mb-6">
        <a
            href="{{ route('admin.email-template-models.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('admin_email_template_models_title') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="__('admin_email_template_model_edit')"
        :description="__('admin_email_template_model_edit_intro')"
    />

    <div class="flow-panel mt-8 max-w-4xl p-8">
        <form method="POST" action="{{ route('admin.email-template-models.update', $model) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.email-template-models._form', ['model' => $model])
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                    <span>{{ __('Save') }}</span>
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
