@props(['variant' => 'neutral'])
@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100',
        'warning' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100',
        'danger' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-100',
        'info' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-100',
        'primary' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-100',
        'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-100',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $slot }}
</span>
