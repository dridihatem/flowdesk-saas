<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider workspace') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel p-8 text-center">
                <p class="text-slate-600 dark:text-slate-400">{{ __('Your account is not linked to a provider profile yet. Ask a company admin to connect your user to a provider record.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
