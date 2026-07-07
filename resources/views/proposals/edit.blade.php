<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Edit quote') }}</p>
                <h2 class="mt-0.5 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $proposal->number ?? $proposal->name }}</h2>
            </div>
            <a href="{{ route('proposals.show', $proposal) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                ← {{ __('Back to quote') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            @include('proposals.partials.quote-editor-form')
        </div>
    </div>
</x-app-layout>
