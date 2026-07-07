@props([
    'href' => null,
    'variant' => 'back',
    'type' => 'button',
])

@php
    $isEdit = $variant === 'edit';
    $base = 'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900';
    $classes = $isEdit
        ? $base.' border-amber-200/90 bg-amber-50 text-amber-950 hover:bg-amber-100 focus:ring-amber-400 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-100 dark:hover:bg-amber-950/70'
        : $base.' border-slate-200/90 bg-slate-100 text-slate-800 hover:bg-slate-200 focus:ring-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700';
    $icon = $isEdit ? 'fa-solid fa-pen-to-square' : 'fa-solid fa-arrow-left';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        <i class="{{ $icon }} text-xs opacity-80" aria-hidden="true"></i>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        <i class="{{ $icon }} text-xs opacity-80" aria-hidden="true"></i>
        {{ $slot }}
    </button>
@endif
