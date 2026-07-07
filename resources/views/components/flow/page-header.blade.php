@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div>
        <h1 class="flow-font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
            {{ $title }}
        </h1>
        @isset($description)
            <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">{{ $description }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
