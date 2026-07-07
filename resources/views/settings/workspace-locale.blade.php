<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('settings_workspace_locale_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('settings_workspace_locale_lead') }}</p>

                <form method="POST" action="{{ route('settings.workspace-locale.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="default_locale" :value="__('settings_workspace_locale_title')" />
                        <select id="default_locale" name="default_locale" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                            @foreach ($locales as $loc)
                                <option value="{{ $loc }}" @selected(old('default_locale', $company->default_locale ?? 'en') === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('settings_workspace_locale_hint') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('default_locale')" />
                    </div>

                    <x-primary-button>{{ __('settings_workspace_locale_save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
