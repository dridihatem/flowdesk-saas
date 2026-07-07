@props([
    'href' => null,
    'label',
    'icon',
    'variant' => 'default',
    'formAction' => null,
])

@php
    $base = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border shadow-sm transition';
    $variants = [
        'default' => 'border-slate-200/80 bg-white text-slate-600 hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400',
        'primary' => 'border-indigo-200/80 bg-indigo-50/80 text-indigo-700 hover:border-indigo-300 hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:border-indigo-700/50',
        'success' => 'border-emerald-200/80 bg-emerald-50/80 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:border-emerald-700/50',
        'danger' => 'border-rose-200/80 bg-rose-50/80 text-rose-700 hover:border-rose-300 hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:border-rose-700/50',
    ];
    $class = $base.' '.($variants[$variant] ?? $variants['default']);
@endphp

@if ($formAction)
    <form method="POST" action="{{ $formAction }}" class="inline">
        @csrf
        <button type="submit" class="{{ $class }}" title="{{ $label }}">
            <span class="sr-only">{{ $label }}</span>
            <i class="{{ $icon }} text-sm" aria-hidden="true"></i>
        </button>
    </form>
@else
    <a href="{{ $href }}" class="{{ $class }}" title="{{ $label }}">
        <span class="sr-only">{{ $label }}</span>
        <i class="{{ $icon }} text-sm" aria-hidden="true"></i>
    </a>
@endif
