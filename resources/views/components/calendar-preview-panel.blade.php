@props(['preview', 'compact' => false, 'showUpcoming' => true])

@php
    $miniCfg = [
        'month' => $preview['month'] ?? now()->format('Y-m'),
        'today' => $preview['today'] ?? now()->toDateString(),
        'dayCounts' => $preview['dayCounts'] ?? [],
        'upcoming' => $preview['upcoming'] ?? [],
        'calendarUrl' => $preview['calendarUrl'] ?? route('calendar.index'),
        'previewUrl' => $preview['previewUrl'] ?? route('calendar.preview'),
        'locale' => flowdesk_intl_locale(),
        'weekdays' => [
            __('calendar_weekday_mon'),
            __('calendar_weekday_tue'),
            __('calendar_weekday_wed'),
            __('calendar_weekday_thu'),
            __('calendar_weekday_fri'),
            __('calendar_weekday_sat'),
            __('calendar_weekday_sun'),
        ],
        'labels' => [
            'upcoming' => __('calendar_upcoming'),
            'no_upcoming' => __('calendar_no_upcoming'),
            'open_calendar' => __('calendar_open_full'),
            'prev_month' => __('calendar_prev_month'),
            'next_month' => __('calendar_next_month'),
            'today' => __('calendar_today'),
        ],
    ];
@endphp

<div
    x-data="flowdeskMiniCalendar({{ \Illuminate\Support\Js::from($miniCfg) }})"
    @class([
        'flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white/80 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50',
        'p-4' => ! $compact,
        'p-3' => $compact,
    ])
>
    <div class="flex items-center justify-between gap-2">
        <h3 @class(['font-semibold text-slate-900 dark:text-white', 'text-sm' => $compact, 'text-base' => ! $compact])>
            {{ __('Calendar') }}
        </h3>
        <a
            :href="calendarUrl"
            class="shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
            x-text="labels.open_calendar"
        ></a>
    </div>

    <div @class(['mt-2' => $compact, 'mt-3' => ! $compact])>
        <div @class([
            'flex w-full items-center gap-1 rounded-xl border border-slate-200/90 bg-slate-50/90 px-1 py-1 dark:border-slate-700 dark:bg-slate-800/60',
            'shadow-inner' => $compact,
        ])>
            <button
                type="button"
                @click="prevMonth()"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-white hover:text-indigo-600 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                :disabled="loading"
                :aria-label="labels.prev_month"
            >
                <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
            </button>
            <p
                @class([
                    'min-w-0 flex-1 truncate text-center font-semibold text-slate-800 dark:text-slate-100',
                    'text-sm' => $compact,
                    'text-base' => ! $compact,
                ])
                x-text="monthLabel"
            ></p>
            <button
                type="button"
                @click="nextMonth()"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-white hover:text-indigo-600 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                :disabled="loading"
                :aria-label="labels.next_month"
            >
                <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
            </button>
        </div>

        <button
            type="button"
            @click="goToday()"
            class="mt-2 w-full rounded-lg border border-indigo-200/80 bg-indigo-50/80 px-2 py-1.5 text-xs font-semibold text-indigo-800 transition hover:bg-indigo-100 disabled:opacity-50 dark:border-indigo-500/30 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
            :disabled="loading || isViewingCurrentMonth()"
            x-text="labels.today"
        ></button>
    </div>

    <div class="relative mt-3 flex-1">
        <div
            x-show="loading"
            x-cloak
            class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/70 dark:bg-slate-900/70"
        >
            <i class="fa-solid fa-spinner fa-spin text-indigo-500" aria-hidden="true"></i>
        </div>

        <div @class(['grid grid-cols-7 text-center', 'gap-1' => $compact, 'gap-0.5' => ! $compact])>
            <template x-for="(wd, i) in weekdays" :key="'mwd-'+i">
                <div @class(['font-semibold uppercase text-slate-400', 'py-1 text-[10px]' => $compact, 'py-0.5 text-[9px]' => ! $compact]) x-text="wd.slice(0, 2)"></div>
            </template>
            <template x-for="(cell, idx) in gridCells" :key="'mc-'+month+'-'+idx">
                <a
                    :href="cell.inMonth && cell.count > 0 ? dayLink(cell.date) : calendarUrl"
                    :class="cellClass(cell, {{ $compact ? 'true' : 'false' }})"
                    :title="cell.count > 0 ? cell.count + ' {{ __('calendar_events_on_day') }}' : ''"
                >
                    <span x-text="cell.day"></span>
                    <span
                        x-show="cell.count > 0"
                        x-cloak
                        class="absolute bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-indigo-500"
                    ></span>
                </a>
            </template>
        </div>
    </div>

    @if ($showUpcoming)
        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="labels.upcoming"></h4>
            <ul class="mt-2 space-y-1.5">
                <li x-show="upcoming.length === 0" x-cloak class="text-xs text-slate-500 dark:text-slate-400" x-text="labels.no_upcoming"></li>
                <template x-for="ev in upcoming" :key="ev.id">
                    <li>
                        <a
                            :href="ev.url || dayLink(ev.date)"
                            class="flex items-start gap-2 rounded-lg px-1 py-1 text-xs transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                        >
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="dotClass(ev.color)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-slate-800 dark:text-slate-200" x-text="ev.title"></span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400" x-text="formatDate(ev.date)"></span>
                            </span>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
    @endif

    {{-- Tailwind safelist for dynamic dots --}}
    <span class="hidden bg-amber-500 bg-rose-500 bg-orange-500 bg-emerald-500 bg-indigo-500 bg-violet-500 bg-cyan-500 bg-fuchsia-500 bg-slate-400"></span>
</div>
