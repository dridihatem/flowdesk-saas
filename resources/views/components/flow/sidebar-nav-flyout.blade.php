@props([
    'label',
    'icon' => 'navigation',
    'items' => [],
    'active' => false,
])

@php
    $items = is_array($items) ? array_values($items) : [];
@endphp

@if (count($items) === 1)
    <x-flow.sidebar-nav-link
        :href="$items[0]['url']"
        :label="$items[0]['label']"
        :icon="$items[0]['icon']"
        :active="$items[0]['active'] ?? false"
    />
@elseif (count($items) > 1)
    <div
        class="flow-nav-flyout"
        x-data="flowdeskSidebarFlyout()"
        @mouseenter="openFlyout($refs.trigger)"
        @mouseleave="scheduleClose()"
        @click.outside="close()"
    >
        <button
            x-ref="trigger"
            type="button"
            @click="toggle($refs.trigger)"
            :aria-expanded="open"
            @class([
                'flow-nav-flyout-trigger flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition duration-150 ease-in-out',
                'flow-nav-item-active' => $active,
                'flow-nav-item-inactive' => ! $active,
            ])
        >
            <x-flow.nav-icon :name="$icon" />
            <span class="min-w-0 flex-1 truncate text-start">{{ $label }}</span>
            <i
                class="fa-solid fa-chevron-right ms-auto shrink-0 text-[10px] opacity-60 transition-transform duration-150 rtl:rotate-180"
                :class="{ 'rotate-90 rtl:rotate-90': open }"
                aria-hidden="true"
            ></i>
        </button>

        <template x-teleport="body">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 ltr:translate-x-1 rtl:-translate-x-1"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 ltr:translate-x-1 rtl:-translate-x-1"
                class="flow-nav-flyout-panel"
                :style="panelStyle"
                @mouseenter="cancelClose()"
                @mouseleave="scheduleClose()"
                x-cloak
            >
                <p class="flow-nav-flyout-title">{{ $label }}</p>
                <div class="space-y-0.5">
                    @foreach ($items as $item)
                        <x-flow.sidebar-nav-link
                            :href="$item['url']"
                            :label="$item['label']"
                            :icon="$item['icon']"
                            :active="$item['active'] ?? false"
                            variant="flyout"
                        />
                    @endforeach
                </div>
            </div>
        </template>
    </div>
@endif
