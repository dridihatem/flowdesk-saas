@props(['preview'])

@php
    $upcomingCount = count($preview['upcoming'] ?? []);
    $todayCount = $preview['dayCounts'][$preview['today'] ?? ''] ?? 0;
    $badgeCount = max($todayCount, $upcomingCount > 0 ? 1 : 0);
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        @keydown.escape.window="open = false"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-flow-text-muted transition hover:bg-flow-surface-muted hover:text-flow-text"
        :aria-expanded="open"
        title="{{ __('Calendar') }}"
        aria-label="{{ __('Calendar') }}"
    >
        <i class="fa-regular fa-calendar-days text-lg" aria-hidden="true"></i>
        @if ($badgeCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-indigo-600 px-1 text-[9px] font-bold text-white">
                {{ $badgeCount > 9 ? '9+' : $badgeCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition
        class="absolute end-0 top-full z-[120] mt-2 w-[min(100vw-2rem,20rem)]"
    >
        <x-calendar-preview-panel :preview="$preview" :compact="true" />
    </div>
</div>
