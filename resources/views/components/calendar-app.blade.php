@props([
    'events' => [],
    'month' => null,
    'calendly' => ['booking_url' => null, 'embed_enabled' => true],
    'isPortal' => false,
    'googleCalendarConnected' => false,
])

@php
    $month = $month ?? now()->format('Y-m');
    $calendarCfg = [
        'events' => $events,
        'month' => $month,
        'canManage' => ! $isPortal,
        'googleConnected' => (bool) $googleCalendarConnected,
        'calendlyUrl' => $calendly['booking_url'] ?? null,
        'routes' => $isPortal ? [] : [
            'store' => route('calendar.events.store'),
            'syncGoogle' => route('calendar.sync.google'),
            'destroy' => url('/calendar/events'),
            'googleSettings' => route('settings.google-calendar'),
        ],
        'labels' => [
            'invoice' => __('calendar_filter_invoices'),
            'project' => __('calendar_filter_projects'),
            'proposal' => __('calendar_filter_proposals'),
            'reminder' => __('calendar_filter_reminders'),
            'payment_due' => __('calendar_filter_payments_due'),
            'payment_received' => __('calendar_filter_payments_received'),
            'meeting' => __('calendar_filter_meetings'),
            'custom' => __('calendar_filter_custom'),
            'today' => __('calendar_today'),
            'no_events' => __('calendar_no_events_day'),
            'upcoming' => __('calendar_upcoming'),
            'events_on_day' => __('calendar_events_on_day'),
            'add_event' => __('calendar_add_event'),
            'event_title' => __('calendar_event_title'),
            'event_description' => __('calendar_event_description'),
            'event_kind' => __('calendar_event_kind'),
            'event_end_date' => __('calendar_event_end_date'),
            'save_event' => __('calendar_save_event'),
            'delete_event' => __('calendar_delete_event'),
            'sync_google' => __('calendar_sync_google'),
            'sync_calendly' => __('calendar_book_calendly'),
            'google_synced' => __('calendar_google_synced_badge'),
            'google_not_connected' => __('calendar_google_not_connected'),
            'sync_failed' => __('calendar_google_sync_failed'),
            'saving' => __('Saving…'),
            'kind_meeting' => __('calendar_kind_meeting'),
            'kind_appointment' => __('calendar_kind_appointment'),
            'kind_reminder' => __('calendar_kind_reminder'),
            'kind_note' => __('calendar_kind_note'),
            'meeting_link_type' => __('calendar_meeting_link_type'),
            'meeting_none' => __('calendar_meeting_none'),
            'meeting_custom_url' => __('calendar_meeting_custom_url'),
            'meeting_zoom' => __('calendar_meeting_zoom'),
            'meeting_google_meet' => __('calendar_meeting_google_meet'),
            'meeting_url_placeholder' => __('calendar_meeting_url_placeholder'),
            'meeting_zoom_hint' => __('calendar_meeting_zoom_hint'),
            'meeting_google_hint' => __('calendar_meeting_google_hint'),
        ],
        'weekdays' => [
            __('calendar_weekday_mon'),
            __('calendar_weekday_tue'),
            __('calendar_weekday_wed'),
            __('calendar_weekday_thu'),
            __('calendar_weekday_fri'),
            __('calendar_weekday_sat'),
            __('calendar_weekday_sun'),
        ],
        'baseUrl' => $isPortal ? route('portal.calendar') : route('calendar.index'),
        'locale' => flowdesk_intl_locale(),
    ];
@endphp

<div
    x-data="flowdeskCalendar()"
    data-calendar-config='@json($calendarCfg)'
    class="space-y-6"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="prevMonth()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                aria-label="{{ __('calendar_prev_month') }}"
            >
                <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
            </button>
            <h3 class="min-w-[10rem] text-center text-lg font-semibold text-slate-900 dark:text-white" x-text="monthLabel"></h3>
            <button
                type="button"
                @click="nextMonth()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                aria-label="{{ __('calendar_next_month') }}"
            >
                <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
            </button>
            <button
                type="button"
                @click="goToday()"
                class="ms-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                {{ __('calendar_today') }}
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            <template x-for="f in filterOptions" :key="f.key">
                <button
                    type="button"
                    @click="toggleFilter(f.key)"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition"
                    :class="filterButtonClass(f.key, f.activeClass, f.inactiveClass)"
                >
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/15" :class="filterDotClass(f.key, f.dotClass)"></span>
                    <span x-text="f.label"></span>
                </button>
            </template>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
            <div class="overflow-x-auto">
                <div class="min-w-[36rem]">
                    <div class="grid grid-cols-7 border-b border-slate-200/80 bg-slate-50/80 dark:border-slate-700/80 dark:bg-slate-800/40">
                        <template x-for="(wd, i) in weekdays" :key="'wd-'+i">
                            <div class="px-1 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="wd"></div>
                        </template>
                    </div>
                    <div class="grid grid-cols-7">
                        <template x-for="(cell, idx) in gridCells" :key="'cell-'+idx">
                            <button
                                type="button"
                                @click="selectDate(cell.date)"
                                class="relative min-h-[5.25rem] border-b border-r border-slate-100 p-1 text-left transition hover:bg-slate-50/80 sm:min-h-[6.25rem] sm:p-1.5 dark:border-slate-800 dark:hover:bg-slate-800/40"
                                :class="cellButtonClass(cell)"
                                :aria-label="cell.events.length ? cell.day + ' — ' + cell.events.length + ' ' + (labels.events_on_day || 'events') : String(cell.day)"
                            >
                                <span class="flex items-start justify-between gap-1">
                                    <span
                                        class="relative inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-medium"
                                        :class="dayNumberClass(cell)"
                                        x-text="cell.day"
                                    ></span>
                                    <span
                                        x-show="cell.events.length > 0"
                                        x-cloak
                                        class="inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-indigo-100 px-1 text-[9px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                                        x-text="cell.events.length"
                                    ></span>
                                </span>

                                <div x-show="cell.events.length > 0" x-cloak class="mt-1 flex flex-wrap gap-0.5">
                                    <template x-for="ev in cellEventPreview(cell)" :key="ev.id">
                                        <span
                                            class="inline-flex h-[1.125rem] w-[1.125rem] items-center justify-center rounded-md text-[8px] text-white shadow-sm ring-1 ring-black/5 dark:ring-white/10"
                                            :class="colorClass(ev.color)"
                                            :title="ev.title"
                                            :aria-label="ev.title"
                                        >
                                            <i class="fa-solid" :class="typeIcon(ev.type)" aria-hidden="true"></i>
                                        </span>
                                    </template>
                                    <span
                                        x-show="cellOverflowCount(cell) > 0"
                                        x-cloak
                                        class="inline-flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-md bg-slate-200 px-0.5 text-[8px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                        x-text="'+' + cellOverflowCount(cell)"
                                    ></span>
                                </div>

                                <div x-show="cell.events.length > 0" x-cloak class="mt-0.5 hidden space-y-0.5 sm:block">
                                    <template x-for="ev in cell.events.slice(0, 2)" :key="'lbl-'+ev.id">
                                        <p
                                            class="truncate text-[9px] leading-tight text-slate-600 dark:text-slate-400"
                                            x-text="ev.title"
                                            :title="ev.title"
                                        ></p>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white" x-text="selectedDayLabel"></h4>

                <div x-show="canManage" x-cloak class="mt-4 space-y-3 rounded-xl border border-dashed border-indigo-200/80 bg-indigo-50/40 p-3 dark:border-indigo-500/30 dark:bg-indigo-950/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300" x-text="labels.add_event"></p>
                    <form class="space-y-2" @submit.prevent="saveEvent()">
                        <input
                            type="text"
                            x-model="eventForm.title"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            :placeholder="labels.event_title"
                            required
                        />
                        <textarea
                            x-model="eventForm.description"
                            rows="2"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            :placeholder="labels.event_description"
                        ></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <select x-model="eventForm.kind" class="rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="meeting" x-text="labels.kind_meeting"></option>
                                <option value="appointment" x-text="labels.kind_appointment"></option>
                                <option value="reminder" x-text="labels.kind_reminder"></option>
                                <option value="note" x-text="labels.kind_note"></option>
                            </select>
                            <input
                                type="date"
                                x-model="eventForm.end_date"
                                class="rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                :min="selectedDate"
                                :title="labels.event_end_date"
                            />
                        </div>
                        <div
                            x-show="['meeting', 'appointment'].includes(eventForm.kind)"
                            x-cloak
                            class="space-y-2 rounded-lg border border-slate-200/80 bg-white/70 p-2 dark:border-slate-700 dark:bg-slate-900/40"
                        >
                            <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="labels.meeting_link_type"></label>
                            <select x-model="eventForm.meeting_link_type" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="none" x-text="labels.meeting_none"></option>
                                <option value="url" x-text="labels.meeting_custom_url"></option>
                                <option value="zoom" x-text="labels.meeting_zoom"></option>
                                <option value="google_meet" x-text="labels.meeting_google_meet"></option>
                            </select>
                            <input
                                x-show="eventForm.meeting_link_type === 'url'"
                                x-cloak
                                type="url"
                                x-model="eventForm.meeting_url"
                                class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                :placeholder="labels.meeting_url_placeholder"
                            />
                            <p x-show="eventForm.meeting_link_type === 'zoom'" x-cloak class="text-[10px] text-slate-500 dark:text-slate-400" x-text="labels.meeting_zoom_hint"></p>
                            <p x-show="eventForm.meeting_link_type === 'google_meet'" x-cloak class="text-[10px] text-slate-500 dark:text-slate-400" x-text="labels.meeting_google_hint"></p>
                        </div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                            :disabled="eventSaving"
                        >
                            <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
                            <span x-text="eventSaving ? labels.saving : labels.save_event"></span>
                        </button>
                    </form>
                    <p x-show="eventFormError" x-text="eventFormError" class="text-xs text-rose-600 dark:text-rose-400"></p>
                    <p x-show="!googleConnected" x-cloak class="text-[10px] text-slate-500 dark:text-slate-400">
                        <a :href="routes.googleSettings" class="text-indigo-600 underline dark:text-indigo-400" x-text="labels.google_not_connected"></a>
                    </p>
                </div>

                <ul class="mt-3 space-y-2">
                    <li x-show="selectedEvents.length === 0" x-cloak class="text-sm text-slate-500 dark:text-slate-400" x-text="labels.no_events"></li>
                    <template x-for="ev in selectedEvents" :key="ev.id">
                        <li class="rounded-lg border border-slate-200/80 p-3 dark:border-slate-700">
                            <a
                                :href="ev.url || '#'"
                                class="block transition"
                                x-bind:class="ev.url ? 'hover:opacity-90' : 'pointer-events-none'"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[10px] text-white" :class="colorClass(ev.color)">
                                        <i class="fa-solid" :class="typeIcon(ev.type)" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white" x-text="ev.title"></p>
                                        <p x-show="ev.subtitle" x-cloak class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400" x-text="ev.subtitle"></p>
                                        <p class="mt-1 text-[10px] uppercase tracking-wide text-slate-400" x-text="typeLabel(ev.type)"></p>
                                        <span x-show="ev.google_synced" x-cloak class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            <i class="fa-brands fa-google text-[9px]" aria-hidden="true"></i>
                                            <span x-text="labels.google_synced"></span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <div x-show="canManage" x-cloak class="mt-2 flex flex-wrap gap-1.5 border-t border-slate-100 pt-2 dark:border-slate-800">
                                <button
                                    type="button"
                                    x-show="googleConnected"
                                    @click="syncGoogle(ev)"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                    :disabled="syncingId === ev.id"
                                >
                                    <i class="fa-brands fa-google text-[9px]" aria-hidden="true"></i>
                                    <span x-text="labels.sync_google"></span>
                                </button>
                                <a
                                    x-show="calendlyUrl && showCalendlyFor(ev)"
                                    :href="calendlyLinkFor(ev)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-800 hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-950/40 dark:text-indigo-200"
                                >
                                    <i class="fa-solid fa-calendar-check text-[9px]" aria-hidden="true"></i>
                                    <span x-text="labels.sync_calendly"></span>
                                </a>
                                <button
                                    type="button"
                                    x-show="ev.can_delete"
                                    @click="deleteEvent(ev)"
                                    class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-500/40 dark:bg-rose-950/40 dark:text-rose-300"
                                >
                                    <i class="fa-solid fa-trash text-[9px]" aria-hidden="true"></i>
                                    <span x-text="labels.delete_event"></span>
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('calendar_upcoming') }}</h4>
                <ul class="mt-3 space-y-2">
                    <li x-show="upcomingEvents.length === 0" x-cloak class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('calendar_no_upcoming') }}
                    </li>
                    <template x-for="ev in upcomingEvents" :key="'up-'+ev.id">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="colorDotClass(ev.color)"></span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-800 dark:text-slate-200" x-text="ev.title"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(ev.date)"></p>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    @if (! empty($calendly['booking_url']))
        <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Book an appointment') }}</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('calendly_book_intro') }}</p>
                </div>
                <a
                    href="{{ $calendly['booking_url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    {{ __('Book now') }}
                </a>
            </div>

            @if (! empty($calendly['embed_enabled']))
                <div class="calendly-inline-widget mt-6 min-h-[640px] w-full overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700" data-url="{{ $calendly['booking_url'] }}" style="min-width:320px;height:640px;"></div>
                @once
                    @push('scripts')
                        <script src="https://assets.calendly.com/assets/external/widget.js" async></script>
                    @endpush
                @endonce
            @endif
        </div>
    @endif

    {{-- Tailwind safelist for dynamic calendar chip colors (Alpine / JS) --}}
    <div class="hidden" aria-hidden="true">
        <span class="bg-rose-500 bg-rose-500/90 bg-cyan-500 bg-cyan-500/90 bg-fuchsia-500 bg-fuchsia-500/90 bg-amber-500 bg-amber-500/90 bg-orange-500 bg-orange-500/90 bg-emerald-500 bg-emerald-500/90 bg-indigo-500 bg-indigo-500/90 bg-violet-500 bg-violet-500/90 bg-slate-500 bg-slate-500/90"></span>
        <span class="border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100"></span>
        <span class="border-cyan-300 bg-cyan-50 text-cyan-900 dark:border-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-100"></span>
        <span class="border-fuchsia-300 bg-fuchsia-50 text-fuchsia-900 dark:border-fuchsia-800 dark:bg-fuchsia-950/40 dark:text-fuchsia-100"></span>
        <span class="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"></span>
        <span class="border-orange-300 bg-orange-50 text-orange-900 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-100"></span>
        <span class="border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"></span>
        <span class="border-indigo-300 bg-indigo-50 text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-100"></span>
        <span class="border-violet-300 bg-violet-50 text-violet-900 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-100"></span>
    </div>
</div>
