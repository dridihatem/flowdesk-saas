<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('email-marketing.templates.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    ← {{ __('email_marketing_templates_title') }}
                </a>
            </div>
            <x-flow.page-header
                :title="__('email_marketing_workspace_template_edit')"
                :description="__('email_marketing_workspace_template_edit_intro')"
            />
            <div class="mt-8 flow-panel p-6 sm:p-8">
                <form method="post" action="{{ route('email-marketing.templates.update', $template) }}" class="space-y-6">
                    @csrf
                    @method('put')
                    @include('email-marketing.templates._workspace-form', ['template' => $template, 'aiAvailable' => $aiAvailable ?? false])
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                        <a href="{{ route('email-marketing.templates.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
