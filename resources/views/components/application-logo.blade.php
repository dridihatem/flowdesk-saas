@props([
    'tagline' => true,
    'inverse' => false,
    'collapsibleWordmark' => false,
])
@php
    $gid = 'fdlogo-'.str_replace('.', '', uniqid('', true));
    $brandName = (string) config('flowdesk.brand_name', 'Flowqil');
@endphp
<div {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    <svg
        class="h-9 w-9 shrink-0 rounded-[11px] shadow-lg shadow-indigo-950/20 ring-1 ring-white/10 sm:h-10 sm:w-10"
        viewBox="0 0 40 40"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <defs>
            <linearGradient id="{{ $gid }}-bg" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                <stop stop-color="#4338ca" />
                <stop offset="0.55" stop-color="#5b21b6" />
                <stop offset="1" stop-color="#312e81" />
            </linearGradient>
            <linearGradient id="{{ $gid }}-shine" x1="20" y1="0" x2="20" y2="20" gradientUnits="userSpaceOnUse">
                <stop stop-color="#ffffff" stop-opacity="0.14" />
                <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#{{ $gid }}-bg)" />
        <rect width="40" height="20" fill="url(#{{ $gid }}-shine)" />
        {{-- Flowqil monogram: flowing "F" + neural node (Aqil / brain) --}}
        <g>
            <rect x="12" y="9.5" width="16.5" height="5" rx="2.5" fill="#ffffff" />
            <rect x="12" y="18" width="12" height="5" rx="2.5" fill="#ffffff" fill-opacity="0.92" />
            <rect x="12" y="9.5" width="5" height="21" rx="2.5" fill="#ffffff" />
            <circle cx="27.5" cy="27.75" r="2.75" fill="#22d3ee" />
        </g>
    </svg>
    <span
        @class([
            'flex min-w-0 flex-col leading-tight',
            'flow-sidebar-wordmark' => $collapsibleWordmark,
        ])
    >
        <span
            @class([
                'font-bold tracking-tight',
                'text-base sm:text-lg',
                'text-slate-900 dark:text-white' => ! $inverse,
                'text-white drop-shadow-sm' => $inverse,
            ])
        >{{ $brandName }}<span @class([
            'text-indigo-600 dark:text-indigo-400' => ! $inverse,
            'text-cyan-300' => $inverse,
        ])>.</span></span>
        @if ($tagline)
            <span
                @class([
                    'text-[10px] font-medium leading-snug sm:text-[11px]',
                    'text-slate-500 dark:text-slate-400' => ! $inverse,
                    'text-white/80' => $inverse,
                ])
            >{{ __('brand_tagline') }}</span>
        @endif
    </span>
</div>
