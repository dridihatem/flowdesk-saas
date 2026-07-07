@props([
    'id',
    'label',
    'description' => null,
    'badge' => null,
])

<section
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-label"
    {{ $attributes->merge(['class' => 'flow-panel scroll-mt-24 overflow-hidden p-0']) }}
>
    <div class="flex flex-col gap-2 border-b border-slate-200/80 bg-gradient-to-r from-indigo-50/70 via-transparent to-transparent px-5 py-4 dark:border-slate-700/80 dark:from-indigo-950/30 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2
                id="{{ $id }}-label"
                class="flow-font-display text-sm font-semibold uppercase tracking-[0.18em] text-slate-600 dark:text-slate-300"
            >
                {{ $label }}
            </h2>
            @if ($description)
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>
        @if ($badge !== null)
            <span class="inline-flex w-fit rounded-full bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">{{ $badge }}</span>
        @endif
    </div>

    <div class="bg-white p-5 dark:bg-slate-900 sm:p-6">
        {{ $slot }}
    </div>
</section>
