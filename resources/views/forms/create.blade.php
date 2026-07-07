<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Create form') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full space-y-8 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <form method="POST" action="{{ route('forms.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Form name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="draft" @selected(old('status') === 'draft')>{{ __('Draft') }}</option>
                            <option value="published" @selected(old('status') === 'published')>{{ __('Published') }}</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                    <div class="flex gap-3">
                        <x-primary-button>{{ __('Continue') }}</x-primary-button>
                        <a href="{{ route('forms.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Embed on your website') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('After you create this form, open it in the editor to copy this snippet with your real form ID. Use a published form and your company fd_live_ API token from') }}
                    <a href="{{ route('settings.widget-embed') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Widget embed') }}</a>.
                </p>
                @if (! $hasApiToken)
                    <p class="mt-2 text-sm text-amber-800 dark:text-amber-200/90">{{ __('You have not generated an API token yet. Generate one under Widget embed before the script can load submissions.') }}</p>
                @endif
                <div class="mt-4">
                    @include('forms.partials.widget-embed-snippet', [
                        'baseUrl' => $baseUrl,
                        'revealedToken' => $apiTokenPlain ?? null,
                        'codeId' => 'flowdesk-form-create-embed',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
