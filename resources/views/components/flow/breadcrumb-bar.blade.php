@props([
    'items' => [],
    'back' => null,
])
@if (! empty($items))
    <div class="flex flex-wrap items-center gap-3 text-sm">
        @if ($back)
            <a
                href="{{ $back }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-flow-border bg-flow-surface/85 px-2.5 py-1.5 text-sm font-medium text-flow-text shadow-sm transition hover:bg-flow-surface dark:border-flow-border dark:bg-flow-surface/80"
            >
                <i class="fa-solid fa-arrow-left text-xs opacity-80 rtl:rotate-180" aria-hidden="true"></i>
                {{ __('Back') }}
            </a>
        @endif
        <nav aria-label="{{ __('Breadcrumb') }}" class="flex min-w-0 flex-wrap items-center gap-1.5 text-flow-text-muted">
            @foreach ($items as $i => $item)
                @if ($i > 0)
                    <span class="text-flow-border" aria-hidden="true">/</span>
                @endif
                @if (! empty($item['href']) && $i < count($items) - 1)
                    <a href="{{ $item['href'] }}" class="truncate font-medium text-flow-primary hover:opacity-90">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="truncate font-semibold text-flow-text">{{ $item['label'] }}</span>
                @endif
            @endforeach
        </nav>
    </div>
@endif
