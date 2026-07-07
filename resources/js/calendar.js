/**
 * Workspace / portal calendar month grid (Alpine).
 */
export function flowdeskCalendarLocale(appLocale) {
    const base = String(appLocale || 'en').split(/[-_]/)[0].toLowerCase();
    const map = { en: 'en-US', fr: 'fr-FR', es: 'es-ES', ar: 'ar-SA' };

    return map[base] || 'en-US';
}

function flowdeskCalendarDateFromParts(y, m, d) {
    return new Date(y, (m || 1) - 1, d || 1);
}

function flowdeskCalendarDateFromIso(iso) {
    const [y, m, d] = String(iso || '').split('-').map(Number);
    return flowdeskCalendarDateFromParts(y, m, d);
}

export function flowdeskFormatCalendarDate(isoOrDate, locale, options = {}) {
    const dt = isoOrDate instanceof Date
        ? isoOrDate
        : flowdeskCalendarDateFromIso(isoOrDate);

    return dt.toLocaleDateString(flowdeskCalendarLocale(locale), options);
}

export function registerFlowdeskCalendar(Alpine) {
    Alpine.data('flowdeskCalendar', (cfg) => {
        const initial = cfg && typeof cfg === 'object' ? cfg : {};

        return {
            events: Array.isArray(initial.events) ? initial.events : [],
            month: initial.month || new Date().toISOString().slice(0, 7),
            labels: initial.labels && typeof initial.labels === 'object' ? initial.labels : {},
            weekdays: Array.isArray(initial.weekdays) ? initial.weekdays : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            baseUrl: initial.baseUrl || window.location.pathname,
            locale: initial.locale || document.documentElement.lang || 'en',
            canManage: !!initial.canManage,
            googleConnected: !!initial.googleConnected,
            calendlyUrl: initial.calendlyUrl || null,
            routes: initial.routes && typeof initial.routes === 'object' ? initial.routes : {},
            selectedDate: null,
            eventForm: { title: '', description: '', kind: 'meeting', end_date: '', meeting_link_type: 'none', meeting_url: '' },
            eventSaving: false,
            eventFormError: null,
            syncingId: null,
            filters: {
                reminder: true,
                payment_due: true,
                payment_received: true,
                meeting: true,
                invoice: true,
                project: true,
                proposal: true,
                custom: true,
            },

            init() {
                const raw = this.$el.getAttribute('data-calendar-config');
                if (raw) {
                    try {
                        const fromAttr = JSON.parse(raw);
                        if (Array.isArray(fromAttr.events)) {
                            this.events = fromAttr.events;
                        }
                        if (fromAttr.month) {
                            this.month = fromAttr.month;
                        }
                        if (fromAttr.labels && typeof fromAttr.labels === 'object') {
                            this.labels = fromAttr.labels;
                        }
                        if (Array.isArray(fromAttr.weekdays)) {
                            this.weekdays = fromAttr.weekdays;
                        }
                        if (fromAttr.baseUrl) {
                            this.baseUrl = fromAttr.baseUrl;
                        }
                        if (fromAttr.locale) {
                            this.locale = fromAttr.locale;
                        }
                        if (typeof fromAttr.canManage === 'boolean') {
                            this.canManage = fromAttr.canManage;
                        }
                        if (typeof fromAttr.googleConnected === 'boolean') {
                            this.googleConnected = fromAttr.googleConnected;
                        }
                        if (fromAttr.calendlyUrl) {
                            this.calendlyUrl = fromAttr.calendlyUrl;
                        }
                        if (fromAttr.routes && typeof fromAttr.routes === 'object') {
                            this.routes = fromAttr.routes;
                        }
                    } catch {
                        // keep defaults
                    }
                }
                this.selectedDate = this.todayIso();
                const params = new URLSearchParams(window.location.search);
                const dayParam = params.get('day');
                if (dayParam && /^\d{4}-\d{2}-\d{2}$/.test(dayParam)) {
                    this.selectedDate = dayParam;
                }
            },

        get filterOptions() {
            return [
                { key: 'invoice', label: this.labels.invoice || 'Invoices', dotClass: 'bg-rose-500', activeClass: 'border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'project', label: this.labels.project || 'Projects', dotClass: 'bg-cyan-500', activeClass: 'border-cyan-300 bg-cyan-50 text-cyan-900 dark:border-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'proposal', label: this.labels.proposal || 'Proposals', dotClass: 'bg-fuchsia-500', activeClass: 'border-fuchsia-300 bg-fuchsia-50 text-fuchsia-900 dark:border-fuchsia-800 dark:bg-fuchsia-950/40 dark:text-fuchsia-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'reminder', label: this.labels.reminder || 'Reminders', dotClass: 'bg-amber-500', activeClass: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'payment_due', label: this.labels.payment_due || 'Due', dotClass: 'bg-orange-500', activeClass: 'border-orange-300 bg-orange-50 text-orange-900 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'payment_received', label: this.labels.payment_received || 'Received', dotClass: 'bg-emerald-500', activeClass: 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'meeting', label: this.labels.meeting || 'Meetings', dotClass: 'bg-indigo-500', activeClass: 'border-indigo-300 bg-indigo-50 text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
                { key: 'custom', label: this.labels.custom || 'Custom', dotClass: 'bg-violet-500', activeClass: 'border-violet-300 bg-violet-50 text-violet-900 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-100', inactiveClass: 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' },
            ];
        },

        toggleFilter(key) {
            if (Object.prototype.hasOwnProperty.call(this.filters, key)) {
                this.filters[key] = !this.filters[key];
            }
        },

        filterButtonClass(key, activeClass, inactiveClass) {
            if (this.filters[key]) {
                return activeClass;
            }

            return inactiveClass || 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400';
        },

        filterDotClass(key, dotClass) {
            return this.filters[key] ? dotClass : dotClass;
        },

        cellButtonClass(cell) {
            const classes = [];
            if (!cell.inMonth) {
                classes.push('bg-slate-50/50', 'dark:bg-slate-900/30');
            } else if (cell.events?.length > 0) {
                classes.push('bg-indigo-50/40', 'dark:bg-indigo-950/25');
            }
            if (this.selectedDate === cell.date) {
                classes.push('ring-2', 'ring-inset', 'ring-indigo-500', 'dark:ring-indigo-400');
            }

            return classes.join(' ');
        },

        dayNumberClass(cell) {
            if (cell.isToday) {
                return 'bg-indigo-600 text-white';
            }
            if (!cell.inMonth) {
                return 'text-slate-400';
            }

            return 'text-slate-800 dark:text-slate-200';
        },

        typeLabel(type) {
            return this.labels[type] || type;
        },

        todayIso() {
            const d = new Date();
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },

        parseMonth() {
            const [y, m] = String(this.month).split('-').map(Number);
            return { year: y, monthIndex: (m || 1) - 1 };
        },

        get monthLabel() {
            const { year, monthIndex } = this.parseMonth();
            return flowdeskFormatCalendarDate(flowdeskCalendarDateFromParts(year, monthIndex + 1, 1), this.locale, {
                month: 'long',
                year: 'numeric',
            });
        },

        filteredEvents() {
            return this.events.filter((ev) => this.filters[ev.type] !== false);
        },

        eventOnDate(ev, iso) {
            const start = ev.date;
            const end = ev.end_date || ev.date;
            return iso >= start && iso <= end;
        },

        eventsForDate(iso) {
            return this.filteredEvents().filter((ev) => this.eventOnDate(ev, iso));
        },

        get gridCells() {
            const { year, monthIndex } = this.parseMonth();
            const first = new Date(year, monthIndex, 1);
            const last = new Date(year, monthIndex + 1, 0);
            const startPad = (first.getDay() + 6) % 7;
            const totalDays = last.getDate();
            const cells = [];
            const today = this.todayIso();

            for (let i = 0; i < startPad; i++) {
                const d = new Date(year, monthIndex, -startPad + i + 1);
                const iso = this.isoFromDate(d);
                cells.push({
                    date: iso,
                    day: d.getDate(),
                    inMonth: false,
                    isToday: iso === today,
                    events: this.eventsForDate(iso),
                });
            }

            for (let day = 1; day <= totalDays; day++) {
                const d = new Date(year, monthIndex, day);
                const iso = this.isoFromDate(d);
                cells.push({
                    date: iso,
                    day,
                    inMonth: true,
                    isToday: iso === today,
                    events: this.eventsForDate(iso),
                });
            }

            while (cells.length % 7 !== 0) {
                const nextDay = cells.length - startPad - totalDays + 1;
                const d = new Date(year, monthIndex + 1, nextDay);
                const iso = this.isoFromDate(d);
                cells.push({
                    date: iso,
                    day: d.getDate(),
                    inMonth: false,
                    isToday: iso === today,
                    events: this.eventsForDate(iso),
                });
            }

            return cells;
        },

        isoFromDate(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },

        selectDate(iso) {
            this.selectedDate = iso;
            this.eventFormError = null;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        async saveEvent() {
            if (!this.canManage || !this.routes.store || !this.selectedDate) {
                return;
            }

            this.eventSaving = true;
            this.eventFormError = null;

            try {
                const res = await fetch(this.routes.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        title: this.eventForm.title,
                        description: this.eventForm.description || null,
                        date: this.selectedDate,
                        end_date: this.eventForm.end_date || null,
                        kind: this.eventForm.kind,
                        meeting_link_type: this.eventForm.meeting_link_type || 'none',
                        meeting_url: this.eventForm.meeting_url || null,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.eventFormError = data.message || this.labels.sync_failed || 'Error';
                    return;
                }

                if (data.event) {
                    this.events.push(data.event);
                }

                this.eventForm = { title: '', description: '', kind: 'meeting', end_date: '', meeting_link_type: 'none', meeting_url: '' };
            } catch {
                this.eventFormError = this.labels.sync_failed || 'Error';
            } finally {
                this.eventSaving = false;
            }
        },

        async deleteEvent(ev) {
            if (!ev?.can_delete || !this.routes.destroy) {
                return;
            }

            const id = String(ev.id || '').replace(/^custom-/, '');
            if (!id) {
                return;
            }

            try {
                const res = await fetch(`${this.routes.destroy}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!res.ok) {
                    return;
                }

                this.events = this.events.filter((item) => item.id !== ev.id);
            } catch {
                // ignore
            }
        },

        async syncGoogle(ev) {
            if (!this.googleConnected || !this.routes.syncGoogle || !ev) {
                return;
            }

            this.syncingId = ev.id;

            try {
                const res = await fetch(this.routes.syncGoogle, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        event_id: ev.id,
                        title: ev.title,
                        date: ev.date,
                        subtitle: ev.subtitle || null,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    ev.google_synced = data.google_synced !== false;
                }
            } catch {
                // ignore
            } finally {
                this.syncingId = null;
            }
        },

        showCalendlyFor(ev) {
            if (!this.calendlyUrl) {
                return false;
            }

            return ev.type === 'meeting' || ev.type === 'custom' || ev.sync_kind === 'custom';
        },

        calendlyLinkFor(ev) {
            if (!this.calendlyUrl) {
                return '#';
            }

            const date = ev?.date || this.selectedDate;
            if (!date) {
                return this.calendlyUrl;
            }

            const separator = this.calendlyUrl.includes('?') ? '&' : '?';

            return `${this.calendlyUrl}${separator}date=${encodeURIComponent(`${date}T09:00:00`)}`;
        },

        get selectedEvents() {
            if (!this.selectedDate) {
                return [];
            }
            return this.eventsForDate(this.selectedDate);
        },

        get selectedDayLabel() {
            if (!this.selectedDate) {
                return '';
            }
            return flowdeskFormatCalendarDate(this.selectedDate, this.locale, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        },

        get upcomingEvents() {
            const today = this.todayIso();
            return this.filteredEvents()
                .filter((ev) => ev.date >= today)
                .slice(0, 8);
        },

        formatDate(iso) {
            return flowdeskFormatCalendarDate(iso, this.locale, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
        },

        colorClass(color) {
            const map = {
                amber: 'bg-amber-500/90',
                rose: 'bg-rose-500/90',
                orange: 'bg-orange-500/90',
                emerald: 'bg-emerald-500/90',
                indigo: 'bg-indigo-500/90',
                violet: 'bg-violet-500/90',
                cyan: 'bg-cyan-500/90',
                fuchsia: 'bg-fuchsia-500/90',
            };

            return map[color] || 'bg-slate-500/90';
        },

        colorDotClass(color) {
            const map = {
                amber: 'bg-amber-500',
                rose: 'bg-rose-500',
                orange: 'bg-orange-500',
                emerald: 'bg-emerald-500',
                indigo: 'bg-indigo-500',
                violet: 'bg-violet-500',
                cyan: 'bg-cyan-500',
                fuchsia: 'bg-fuchsia-500',
            };

            return map[color] || 'bg-slate-400';
        },

        typeIcon(type) {
            const map = {
                invoice: 'fa-file-invoice-dollar',
                project: 'fa-diagram-project',
                proposal: 'fa-file-signature',
                reminder: 'fa-bell',
                payment_due: 'fa-clock',
                payment_received: 'fa-circle-check',
                meeting: 'fa-calendar-check',
                custom: 'fa-star',
            };

            return map[type] || 'fa-circle';
        },

        cellEventPreview(cell, limit = 5) {
            if (!cell?.events?.length) {
                return [];
            }

            return cell.events.slice(0, limit);
        },

        cellOverflowCount(cell, limit = 5) {
            if (!cell?.events?.length) {
                return 0;
            }

            return Math.max(0, cell.events.length - limit);
        },

        shiftMonth(delta) {
            const { year, monthIndex } = this.parseMonth();
            const d = new Date(year, monthIndex + delta, 1);
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            const url = new URL(this.baseUrl, window.location.origin);
            url.searchParams.set('month', this.month);
            window.location.assign(url.toString());
        },

        prevMonth() {
            this.shiftMonth(-1);
        },

        nextMonth() {
            this.shiftMonth(1);
        },

        goToday() {
            const d = new Date();
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            this.selectedDate = this.todayIso();
            const url = new URL(this.baseUrl, window.location.origin);
            url.searchParams.set('month', this.month);
            window.location.assign(url.toString());
        },
        };
    });

    Alpine.data('flowdeskMiniCalendar', (cfg = {}) => ({
        month: cfg.month || new Date().toISOString().slice(0, 7),
        today: cfg.today || new Date().toISOString().slice(0, 10),
        dayCounts: cfg.dayCounts && typeof cfg.dayCounts === 'object' ? cfg.dayCounts : {},
        weekdays: Array.isArray(cfg.weekdays) ? cfg.weekdays : ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
        upcoming: Array.isArray(cfg.upcoming) ? cfg.upcoming : [],
        calendarUrl: cfg.calendarUrl || '/calendar',
        previewUrl: cfg.previewUrl || null,
        locale: cfg.locale || document.documentElement.lang || 'en',
        labels: cfg.labels && typeof cfg.labels === 'object' ? cfg.labels : {},
        loading: false,

        parseMonth() {
            const [y, m] = String(this.month).split('-').map(Number);
            return { year: y, monthIndex: (m || 1) - 1 };
        },

        get monthLabel() {
            const { year, monthIndex } = this.parseMonth();
            return flowdeskFormatCalendarDate(flowdeskCalendarDateFromParts(year, monthIndex + 1, 1), this.locale, {
                month: 'long',
                year: 'numeric',
            });
        },

        isoFromDate(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        },

        countFor(iso) {
            return Number(this.dayCounts[iso] || 0);
        },

        async shiftMonth(delta) {
            const { year, monthIndex } = this.parseMonth();
            const d = new Date(year, monthIndex + delta, 1);
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            await this.fetchMonth();
        },

        prevMonth() {
            return this.shiftMonth(-1);
        },

        nextMonth() {
            return this.shiftMonth(1);
        },

        async goToday() {
            const d = new Date();
            this.month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            await this.fetchMonth();
        },

        isViewingCurrentMonth() {
            const now = new Date();
            const current = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            return this.month === current;
        },

        async fetchMonth() {
            if (!this.previewUrl) {
                return;
            }

            this.loading = true;
            try {
                const url = new URL(this.previewUrl, window.location.origin);
                url.searchParams.set('month', this.month);
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    return;
                }
                if (data.month) {
                    this.month = data.month;
                }
                if (data.dayCounts && typeof data.dayCounts === 'object') {
                    this.dayCounts = data.dayCounts;
                }
                if (Array.isArray(data.upcoming)) {
                    this.upcoming = data.upcoming;
                }
            } finally {
                this.loading = false;
            }
        },

        get gridCells() {
            const { year, monthIndex } = this.parseMonth();
            const first = new Date(year, monthIndex, 1);
            const last = new Date(year, monthIndex + 1, 0);
            const startPad = (first.getDay() + 6) % 7;
            const totalDays = last.getDate();
            const cells = [];

            for (let i = 0; i < startPad; i++) {
                const d = new Date(year, monthIndex, -startPad + i + 1);
                const iso = this.isoFromDate(d);
                cells.push({ date: iso, day: d.getDate(), inMonth: false, isToday: iso === this.today, count: this.countFor(iso) });
            }

            for (let day = 1; day <= totalDays; day++) {
                const d = new Date(year, monthIndex, day);
                const iso = this.isoFromDate(d);
                cells.push({ date: iso, day, inMonth: true, isToday: iso === this.today, count: this.countFor(iso) });
            }

            while (cells.length % 7 !== 0) {
                const nextDay = cells.length - startPad - totalDays + 1;
                const d = new Date(year, monthIndex + 1, nextDay);
                const iso = this.isoFromDate(d);
                cells.push({ date: iso, day: d.getDate(), inMonth: false, isToday: iso === this.today, count: this.countFor(iso) });
            }

            return cells;
        },

        cellClass(cell, compact = false) {
            const classes = compact
                ? ['relative mx-auto flex h-8 w-full max-w-[2rem] items-center justify-center rounded-lg text-[11px]']
                : ['relative flex h-7 w-7 items-center justify-center rounded-md text-[11px]'];
            if (!cell.inMonth) {
                classes.push('text-slate-300 dark:text-slate-600');
            } else if (cell.isToday) {
                classes.push('bg-indigo-600 font-semibold text-white');
            } else if (cell.count > 0) {
                classes.push('bg-indigo-50 font-medium text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200');
            } else {
                classes.push('text-slate-700 dark:text-slate-300');
            }

            return classes.join(' ');
        },

        dotClass(color) {
            const map = {
                amber: 'bg-amber-500',
                rose: 'bg-rose-500',
                orange: 'bg-orange-500',
                emerald: 'bg-emerald-500',
                indigo: 'bg-indigo-500',
                violet: 'bg-violet-500',
                cyan: 'bg-cyan-500',
                fuchsia: 'bg-fuchsia-500',
            };

            return map[color] || 'bg-slate-400';
        },

        formatDate(iso) {
            return flowdeskFormatCalendarDate(iso, this.locale, {
                month: 'short',
                day: 'numeric',
            });
        },

        dayLink(iso) {
            const url = new URL(this.calendarUrl, window.location.origin);
            url.searchParams.set('month', iso.slice(0, 7));
            url.searchParams.set('day', iso);

            return url.toString();
        },
    }));
}
