@props(['label', 'variant' => 'indigo'])
@php
    $gradients = [
        'indigo' => 'from-indigo-500 via-violet-500 to-purple-600',
        'cyan' => 'from-cyan-500 via-sky-500 to-blue-600',
        'emerald' => 'from-emerald-500 via-teal-500 to-cyan-600',
        'amber' => 'from-amber-400 via-orange-500 to-rose-500',
    ];
    $g = $gradients[$variant] ?? $gradients['indigo'];
@endphp
<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-white/10 bg-white/60 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:bg-slate-900/40 dark:ring-white/10']) }}>
    <div class="pointer-events-none absolute inset-0 opacity-[0.12] bg-gradient-to-br dark:opacity-[0.18] {{ $g }}"></div>
    <div class="relative">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
            {{ $slot }}
        </div>
    </div>
</div>
