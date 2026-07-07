<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('New quote') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8">
            @include('proposals.partials.quote-editor-form')
        </div>
    </div>
</x-app-layout>
