import './bootstrap';
import './guest-forms';
import { flowdeskNotify } from './flowdesk-notify';

window.flowdeskNotify = flowdeskNotify;

import Alpine from 'alpinejs';
import { registerAiVoiceField, registerFlowdeskAiFormVoiceBridge } from './ai-voice';
import { registerAiWritingModes } from './ai-writing-modes';
import { registerNovaVoiceNav } from './nova-voice-nav';
import { registerNovaAssistant } from './nova-assistant';
import { registerSidebarFlyout } from './sidebar-flyout';
import { registerFlowdeskCalendar } from './calendar';
import Sortable from 'sortablejs';
import { createApp } from 'vue';
import FlowdeskPulse from './vue/FlowdeskPulse.vue';

window.Alpine = Alpine;

/**
 * Match PHP number_format($num, $decimals, $decimal_separator, $thousands_separator).
 */
function flowdeskNumberFormat(num, decimals, decPoint, thousandsSep) {
    const d = Math.max(0, Math.floor(Number(decimals)) || 0);
    const dec = decPoint ?? '.';
    const thou = thousandsSep ?? ',';
    const x = Number(num);
    if (!Number.isFinite(x)) {
        if (d === 0) {
            return '0';
        }
        return `0${dec}${'0'.repeat(d)}`;
    }
    const neg = x < 0 || Object.is(x, -0);
    const abs = Math.abs(x);
    const body = d === 0 ? String(Math.round(abs)) : abs.toFixed(d);
    const parts = body.split('.');
    let intPart = parts[0];
    const fracPart = parts[1];
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thou);
    let out = intPart;
    if (d > 0 && fracPart !== undefined) {
        out += dec + fracPart;
    }
    return neg ? `-${out}` : out;
}

document.addEventListener('alpine:init', () => {
    registerFlowdeskAiFormVoiceBridge();
    registerAiVoiceField(Alpine);
    registerAiWritingModes(Alpine);
    registerNovaVoiceNav(Alpine);
    registerNovaAssistant(Alpine);
    registerSidebarFlyout(Alpine);
    registerFlowdeskCalendar(Alpine);

    Alpine.data('dashboardLayoutEditor', (initial) => ({
        rows: initial.rows,
        saveUrl: initial.saveUrl,
        csrf: initial.csrf,
        redirectUrl: initial.redirectUrl ?? '/settings/dashboard',
        init() {
            this.$nextTick(() => {
                const el = this.$refs.sortList;
                if (! el) {
                    return;
                }
                Sortable.create(el, {
                    animation: 150,
                    handle: '[data-drag-handle]',
                    onEnd: () => {
                        const keys = [...el.children].map((n) => n.dataset.widgetKey);
                        const next = [];
                        keys.forEach((k) => {
                            const row = this.rows.find((r) => r.key === k);
                            if (row) {
                                next.push(row);
                            }
                        });
                        this.rows = next;
                        this.rows.forEach((r, i) => {
                            r.order = i;
                        });
                    },
                });
            });
        },
        async saveLayout() {
            const body = {
                widgets: this.rows.map((r, i) => ({
                    key: r.key,
                    enabled: !!r.enabled,
                    order: i,
                })),
            };
            const res = await fetch(this.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            if (res.ok) {
                window.location.assign(this.redirectUrl);
                return;
            }
            let message = `HTTP ${res.status}`;
            try {
                const data = await res.json();
                if (data.message) {
                    message = data.message;
                }
                if (data.errors) {
                    message = Object.values(data.errors).flat().join(' ') || message;
                }
            } catch {
                // ignore
            }
            window.alert(message);
        },
    }));

    const buildInvoiceForm = (items, taxPreview, clientsStoreUrl, currencyMoneyMeta = {}, localeAmountSep = {}) => ({
        items,
        taxPreview,
        clientsStoreUrl,
        currencyMoneyMeta,
        amountDec: localeAmountSep.decimal ?? '.',
        amountThou: localeAmountSep.thousands ?? ',',
        minorScale: 100,
        fractionDigits: 2,
        currencyCode: 'USD',
        clientMode: 'pick',
        extraClients: [],
        selectedQuickId: null,
        quickOpen: false,
        quickName: '',
        quickEmail: '',
        quickPhone: '',
        quickSource: '',
        quickAddressLine1: '',
        quickAddressCity: '',
        quickAddressCountry: '',
        quickError: '',
        quickLoading: false,
        quickFailMsg: 'Could not save client.',
        _quickClientVoiceHandler: null,
        resetQuickClientForm() {
            this.quickName = '';
            this.quickEmail = '';
            this.quickPhone = '';
            this.quickSource = '';
            this.quickAddressLine1 = '';
            this.quickAddressCity = '';
            this.quickAddressCountry = '';
            this.quickError = '';
        },
        openQuickClientModal(prefill = {}) {
            this.quickError = '';
            this.clientMode = 'pick';
            if (prefill?.name) {
                this.quickName = String(prefill.name).trim();
            } else {
                this.resetQuickClientForm();
            }
            this.quickOpen = true;
        },
        init() {
            const mode = this.$root.dataset.clientMode;
            if (mode === 'new' || mode === 'pick') {
                this.clientMode = mode;
            }
            if (this.$root.dataset.quickError) {
                this.quickFailMsg = this.$root.dataset.quickError;
            }
            const sel = document.getElementById('currency');
            const fromSelect = sel?.value ? String(sel.value).toUpperCase() : '';
            const fromPreview = this.taxPreview?.invoice_currency
                ? String(this.taxPreview.invoice_currency).toUpperCase()
                : '';
            const initial = fromSelect || fromPreview || 'USD';
            this.syncCurrencyMoney(initial, false);

            if (this.clientsStoreUrl) {
                this._quickClientVoiceHandler = (event) => {
                    const name = String(event?.detail?.name || '').trim();
                    this.openQuickClientModal(name ? { name } : {});
                };
                document.addEventListener('flowdesk-open-quick-client', this._quickClientVoiceHandler);
            }
        },
        syncCurrencyMoney(code, convertLines = true) {
            const c = String(code || 'USD').toUpperCase();
            const newMeta = this.currencyMoneyMeta[c] || { scale: 100, fractionDigits: 2 };
            const oldScale = this.minorScale;
            const newScale = Number(newMeta.scale) > 0 ? Number(newMeta.scale) : 100;
            if (convertLines && oldScale > 0 && newScale > 0 && oldScale !== newScale) {
                this.items.forEach((line) => {
                    const oldMinor = Math.round((Number(line.unit_major) || 0) * oldScale);
                    line.unit_major = oldMinor / newScale;
                });
            }
            this.currencyCode = c;
            this.minorScale = newScale;
            this.fractionDigits = Number(newMeta.fractionDigits) >= 0 ? Number(newMeta.fractionDigits) : 2;
        },
        fmt(n) {
            return String(Math.trunc(Number(n) || 0));
        },
        async submitQuickClient() {
            this.quickError = '';
            this.quickLoading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const body = new URLSearchParams();
                body.set('name', this.quickName.trim());
                if (this.quickEmail.trim()) {
                    body.set('email', this.quickEmail.trim());
                }
                if (this.quickPhone.trim()) {
                    body.set('phone', this.quickPhone.trim());
                }
                if (this.quickSource) {
                    body.set('source', this.quickSource);
                }
                if (this.quickAddressLine1.trim()) {
                    body.set('address_line1', this.quickAddressLine1.trim());
                }
                if (this.quickAddressCity.trim()) {
                    body.set('address_city', this.quickAddressCity.trim());
                }
                if (this.quickAddressCountry.trim()) {
                    body.set('address_country', this.quickAddressCountry.trim().toUpperCase());
                }
                const r = await fetch(this.clientsStoreUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    credentials: 'same-origin',
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                    this.quickError = first || data.message || this.quickFailMsg;
                    return;
                }
                if (data.client) {
                    this.extraClients.push({ id: data.client.id, name: data.client.name });
                    this.selectedQuickId = data.client.id;
                    this.clientMode = 'pick';
                    this.quickOpen = false;
                    this.resetQuickClientForm();
                    this.$nextTick(() => {
                        const sel = document.getElementById('client_id');
                        if (sel) {
                            sel.value = data.client.id;
                        }
                    });
                }
            } catch (e) {
                this.quickError = this.quickFailMsg;
            } finally {
                this.quickLoading = false;
            }
        },
    });

    /** Overrides for buildInvoiceForm — must follow `{ ...baseObj, ...mixin }` so getters stay reactive. */
    const documentLineFormMixin = (baseObj) => ({
        linesRevision: 0,
        init() {
            baseObj.init.call(this);
            if (!Array.isArray(this.items)) {
                this.items = [];
            }
            this.items.forEach((line) => {
                this.hydrateLineAmounts(line);
            });
            this.touchLines();
        },
        hydrateLineAmounts(line) {
            const scale = this.minorScale > 0 ? this.minorScale : 100;
            const display = String(line.unit_display ?? '').trim();
            let minor = Math.round(Number(line.unit_amount_minor));

            if (display !== '') {
                minor = Math.round(this.parseMajorInput(display) * scale);
            } else if (!Number.isFinite(minor) || minor <= 0) {
                minor = Math.round((Number(line.unit_major) || 0) * scale);
            }

            line.unit_amount_minor = minor;
            line.unit_display = this.formatMajorFromMinor(minor);
            line.unit_major = scale > 0 ? minor / scale : 0;
        },
        syncLineMinorFromDisplay(line) {
            const scale = this.minorScale > 0 ? this.minorScale : 100;
            const minor = Math.round(this.parseMajorInput(line.unit_display) * scale);
            line.unit_amount_minor = minor;
            line.unit_major = scale > 0 ? minor / scale : 0;

            return minor;
        },
        touchLines() {
            this.linesRevision += 1;
        },
        get lineSubtotal() {
            void this.linesRevision;

            return (Array.isArray(this.items) ? this.items : []).reduce((s, l) => {
                void l.quantity;
                void l.unit_display;

                return s + (Number(l.quantity) || 0) * this.unitMinor(l);
            }, 0);
        },
        get totals() {
            void this.linesRevision;
            const sub = this.lineSubtotal;
            const pct = Number(this.taxPreview.vat_percent) || 0;
            const vat = pct > 0 ? Math.round(sub * (pct / 100)) : 0;
            const stamp =
                this.taxPreview.fiscal_stamp_enabled && Number(this.taxPreview.fiscal_stamp_minor) > 0
                    ? Number(this.taxPreview.fiscal_stamp_minor)
                    : 0;

            return { subtotal: sub, vat, stamp, total: sub + vat + stamp };
        },
        lineHt(line) {
            void this.linesRevision;
            void line.quantity;
            void line.unit_display;

            return Math.round((Number(line.quantity) || 0) * this.unitMinor(line));
        },
        lineVat(line) {
            void this.linesRevision;
            const ht = this.lineHt(line);
            const sub = this.lineSubtotal;
            const totalVat = this.totals.vat;
            if (ht <= 0 || sub <= 0 || totalVat <= 0) {
                return 0;
            }

            return Math.round((ht * totalVat) / sub);
        },
        lineTtc(line) {
            void this.linesRevision;

            return this.lineHt(line) + this.lineVat(line);
        },
        onLineQtyInput() {
            this.touchLines();
        },
        onLinePriceInput(line) {
            this.syncLineMinorFromDisplay(line);
            this.touchLines();
        },
        onLinePriceBlur(line) {
            this.syncLineMinorFromDisplay(line);
            line.unit_display = this.formatMajorFromMinor(line.unit_amount_minor);
            this.touchLines();
        },
        inputFractionDigits() {
            if (this.currencyCode === 'TND') {
                return 3;
            }

            return this.fractionDigits > 0 ? this.fractionDigits : 2;
        },
        pricePlaceholder() {
            const fd = this.inputFractionDigits();

            return '0' + this.amountDec + (fd > 0 ? '0'.repeat(fd) : '');
        },
        formatMajorInput(n) {
            const fd = this.inputFractionDigits();
            const num = Number(n) || 0;
            if (fd <= 0) {
                return String(Math.round(num));
            }
            let fixed = num.toFixed(fd);
            fixed = fixed.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');

            return fixed.replace('.', this.amountDec);
        },
        formatMajorFromMinor(minor) {
            const scale = this.minorScale > 0 ? this.minorScale : 100;
            const fd = this.inputFractionDigits();
            let m = Math.trunc(Number(minor) || 0);
            const neg = m < 0;
            const abs = Math.abs(m);
            const whole = Math.floor(abs / scale);
            const rem = abs % scale;
            if (fd <= 0) {
                return String(neg ? -whole : whole);
            }
            let fracStr = String(rem).padStart(fd, '0');
            if (fracStr.length > fd) {
                fracStr = fracStr.slice(0, fd);
            }
            fracStr = fracStr.replace(/0+$/, '');
            const major = fracStr ? parseFloat(`${whole}.${fracStr}`) : whole;

            return this.formatMajorInput(neg ? -major : major);
        },
        parseMajorInput(str) {
            const raw = String(str ?? '')
                .trim()
                .replace(new RegExp('\\' + this.amountThou, 'g'), '')
                .replace(this.amountDec, '.');
            const n = parseFloat(raw);

            return Number.isFinite(n) ? n : 0;
        },
        unitMinor(line) {
            const display = String(line.unit_display ?? '').trim();
            if (display !== '') {
                return Math.round(this.parseMajorInput(display) * this.minorScale);
            }
            const cached = Math.round(Number(line.unit_amount_minor));
            if (Number.isFinite(cached) && cached > 0) {
                return cached;
            }

            return Math.round((Number(line.unit_major) || 0) * this.minorScale);
        },
        fmtMinor(n) {
            const scale = this.minorScale > 0 ? this.minorScale : 100;
            const fd = this.inputFractionDigits();
            let minor = Math.trunc(Number(n) || 0);
            const neg = minor < 0;
            const a = Math.abs(minor);
            const whole = Math.floor(a / scale);
            const rem = a % scale;
            if (fd <= 0) {
                const v = neg ? -whole : whole;

                return flowdeskNumberFormat(v, 0, this.amountDec, this.amountThou);
            }
            let fracStr = String(rem).padStart(fd, '0');
            if (fracStr.length > fd) {
                fracStr = fracStr.slice(0, fd);
            }
            const left = flowdeskNumberFormat(neg ? -whole : whole, 0, this.amountDec, this.amountThou);

            return left + this.amountDec + fracStr;
        },
        syncCurrencyMoney(code, convertLines = true) {
            baseObj.syncCurrencyMoney.call(this, code, convertLines);
            this.items.forEach((line) => {
                this.hydrateLineAmounts(line);
            });
            this.touchLines();
        },
        mapAiSuggestedLines(rows) {
            return rows.map((row) => {
                const minor = Math.round((Number(row.unit_major) || 0) * this.minorScale);

                return {
                    description: row.description ?? '',
                    quantity: Number(row.quantity) || 1,
                    unit_major: Number(row.unit_major) || 0,
                    unit_amount_minor: minor,
                    unit_display: this.formatMajorFromMinor(minor),
                };
            });
        },
    });

    /** Merge base form data with line-calc mixin without invoking getters via spread/assign. */
    const composeDocumentLineForm = (baseObj, extras = {}) => {
        const form = {};

        for (const key of Object.keys(baseObj)) {
            const desc = Object.getOwnPropertyDescriptor(baseObj, key);
            if (desc && !desc.get && !desc.set) {
                Object.defineProperty(form, key, desc);
            }
        }

        const mixin = documentLineFormMixin(baseObj);
        for (const key of Object.keys(mixin)) {
            const desc = Object.getOwnPropertyDescriptor(mixin, key);
            if (desc) {
                Object.defineProperty(form, key, desc);
            }
        }

        for (const key of Object.keys(extras)) {
            const desc = Object.getOwnPropertyDescriptor(extras, key);
            if (desc) {
                Object.defineProperty(form, key, desc);
            }
        }

        return form;
    };

    const buildDocumentLineAiSuggest = (briefRequiredMsg) =>
        async function suggestAiLines() {
            const url = this.aiConfig?.url;
            if (!url) {
                return;
            }
            const brief = String(this.aiBrief ?? '').trim();
            if (brief.length < 10) {
                this.aiError = this.aiConfig?.errBrief || briefRequiredMsg;

                return;
            }
            this.aiLoading = true;
            this.aiError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const payload = { brief, replace: this.aiReplace };
                if (this.aiConfig.draft) {
                    payload.currency = this.currencyCode;
                    const nameEl = document.getElementById('name');
                    if (nameEl?.value) {
                        payload.name = nameEl.value;
                    }
                    const clientSel = document.getElementById('client_id');
                    if (clientSel?.value) {
                        payload.client_id = clientSel.value;
                    }
                    const projectSel = document.getElementById('project_id');
                    if (projectSel?.value) {
                        payload.project_id = projectSel.value;
                    }
                    const validEl = document.getElementById('valid_until');
                    if (validEl?.value) {
                        payload.valid_until = validEl.value;
                    }
                }
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin',
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    this.aiError = data.message || this.aiConfig?.errNetwork || 'AI request failed.';

                    return;
                }
                const rows = Array.isArray(data.items) ? data.items : [];
                const mapped = this.mapAiSuggestedLines(rows);
                if (mapped.length === 0) {
                    this.aiError = this.aiConfig?.errEmpty || 'No lines returned.';

                    return;
                }
                this.items = this.aiReplace ? mapped : [...this.items, ...mapped];
                this.aiBrief = '';
                this.touchLines();
            } catch {
                this.aiError = this.aiConfig?.errNetwork || 'Network error.';
            } finally {
                this.aiLoading = false;
            }
        };

    const buildDocumentLineScan = () =>
        async function scanDocumentLines() {
            const url = this.aiConfig?.scanUrl;
            if (!url || !this.scanFile) {
                this.scanError = this.aiConfig?.errScanFile || 'Choose a document first.';

                return;
            }
            this.scanLoading = true;
            this.scanError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const form = new FormData();
                form.append('document', this.scanFile);
                form.append('replace', this.scanReplace ? '1' : '0');
                if (this.aiConfig.draft) {
                    form.append('currency', this.currencyCode);
                }
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: form,
                    credentials: 'same-origin',
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    this.scanError = data.message || this.aiConfig?.errScanNetwork || 'Scan failed.';

                    return;
                }
                const rows = Array.isArray(data.items) ? data.items : [];
                const mapped = this.mapAiSuggestedLines(rows);
                if (mapped.length === 0) {
                    this.scanError = this.aiConfig?.errScanEmpty || 'No lines returned.';

                    return;
                }
                this.items = this.scanReplace ? mapped : [...this.items, ...mapped];
                this.scanFile = null;
                if (this.$refs.scanFileInput) {
                    this.$refs.scanFileInput.value = '';
                }
                this.touchLines();
            } catch {
                this.scanError = this.aiConfig?.errScanNetwork || 'Network error.';
            } finally {
                this.scanLoading = false;
            }
        };

    const documentLineAiExtras = (aiConfig, briefRequiredMsg) => {
        if (!aiConfig?.url) {
            return {};
        }

        const extras = {
            aiConfig,
            aiBrief: aiConfig.initialBrief || '',
            aiLoading: false,
            aiError: '',
            aiReplace: true,
            suggestAiLines: buildDocumentLineAiSuggest(briefRequiredMsg),
        };

        if (aiConfig.scanUrl) {
            extras.scanFile = null;
            extras.scanLoading = false;
            extras.scanError = '';
            extras.scanReplace = true;
            extras.scanDocumentLines = buildDocumentLineScan();
        }

        return extras;
    };

    Alpine.data('invoiceForm', (items, taxPreview, clientsStoreUrl, currencyMoneyMeta, localeAmountSep, aiConfig = null) => {
        const baseObj = buildInvoiceForm(items, taxPreview, clientsStoreUrl, currencyMoneyMeta, localeAmountSep);

        return composeDocumentLineForm(
            baseObj,
            documentLineAiExtras(aiConfig, 'Describe what to invoice (min. 10 characters).'),
        );
    });

    Alpine.data('quoteForm', (items, taxPreview, currencyMoneyMeta, localeAmountSep, aiConfig = {}, clientsStoreUrl = '') => {
        const baseObj = buildInvoiceForm(items, taxPreview, clientsStoreUrl, currencyMoneyMeta, localeAmountSep);

        return composeDocumentLineForm(
            baseObj,
            documentLineAiExtras(aiConfig, 'Describe what to quote (min. 10 characters).'),
        );
    });

    Alpine.data('projectDescriptionAi', (cfg) => ({
        open: false,
        busy: false,
        err: null,
        prompt: '',
        applyMode: 'replace',
        suggestUrl: cfg.suggestUrl,
        textareaId: cfg.textareaId || 'description',
        errEmpty: cfg.errEmpty || 'Empty response',
        errNetwork: cfg.errNetwork || 'Network error',
        errPromptRequired: cfg.errPromptRequired || 'Enter instructions first.',
        openModal() {
            this.open = true;
            this.err = null;
        },
        closeModal() {
            this.open = false;
        },
        async generate() {
            const ta = document.getElementById('ai_desc_prompt');
            const promptText = (ta?.value ?? this.prompt ?? '').trim();
            if (!promptText) {
                this.err = this.errPromptRequired;
                return;
            }
            this.prompt = promptText;
            this.busy = true;
            this.err = null;
            const replace = this.applyMode === 'replace';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const r = await fetch(this.suggestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        mode: 'project_description',
                        context: promptText,
                    }),
                    credentials: 'same-origin',
                });
                const text = await r.text();
                let data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch {
                    data = {};
                }
                if (!r.ok) {
                    this.err = data.message || `HTTP ${r.status}`;
                    return;
                }
                const html = typeof data.suggestion === 'string' ? data.suggestion : '';
                if (html.trim() === '') {
                    this.err = this.errEmpty;
                    return;
                }
                const id = this.textareaId;
                const $ = window.jQuery;
                const ta = document.getElementById(id);
                let applied = false;
                if ($ && ta && $.fn.summernote) {
                    try {
                        const $el = $(ta);
                        const cur = $el.summernote('code');
                        const merged = replace
                            ? html
                            : `${typeof cur === 'string' ? cur : ''}<br><br>${html}`;
                        $el.summernote('code', merged);
                        applied = true;
                    } catch {
                        /* Summernote not initialized on this field */
                    }
                }
                if (!applied && ta) {
                    const stripped = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    if (replace) {
                        ta.value = stripped;
                    } else {
                        ta.value = `${ta.value || ''}\n${stripped}`;
                    }
                }
                this.closeModal();
                this.prompt = '';
            } catch (e) {
                this.err = this.errNetwork;
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('invoicePaymentQuickFill', (cfg) => ({
        totalMinor: Number(cfg.totalMinor) || 0,
        paidMinor: Number(cfg.paidMinor) || 0,
        balanceMinor: Number(cfg.balanceMinor) || 0,
        scale: Number(cfg.scale) > 0 ? Number(cfg.scale) : 100,
        fractionDigits: Number(cfg.fractionDigits) >= 0 ? Number(cfg.fractionDigits) : 2,
        dec: cfg.dec ?? '.',
        thou: cfg.thou ?? ',',
        currency: cfg.currency ?? 'USD',
        picks: Array.isArray(cfg.picks) ? cfg.picks : [],
        draftRemainingMinor: Number(cfg.balanceMinor) || 0,
        parseMajorToMinor(str) {
            const raw = String(str ?? '')
                .trim()
                .replace(/\s/g, '')
                .replace(',', '.');
            if (raw === '') {
                return 0;
            }
            const n = Number.parseFloat(raw);
            if (!Number.isFinite(n)) {
                return 0;
            }
            return Math.round(n * this.scale);
        },
        fmtDisplay(minor) {
            const m = Math.trunc(Number(minor) || 0);
            const scale = this.scale;
            const fd = this.fractionDigits;
            const neg = m < 0;
            const a = Math.abs(m);
            const whole = Math.floor(a / scale);
            const rem = a % scale;
            if (fd <= 0) {
                const v = neg ? -whole : whole;
                return flowdeskNumberFormat(v, 0, this.dec, this.thou);
            }
            let fracStr = String(rem).padStart(fd, '0');
            if (fracStr.length > fd) {
                fracStr = fracStr.slice(0, fd);
            }
            const left = flowdeskNumberFormat(neg ? -whole : whole, 0, this.dec, this.thou);
            return left + this.dec + fracStr;
        },
        onAmountInput(e) {
            const minor = this.parseMajorToMinor(e.target.value);
            this.draftRemainingMinor = Math.max(0, this.balanceMinor - minor);
        },
        applyQuickPick(amountMajor, amountMinor, percent) {
            const el = document.getElementById('amount');
            if (el) {
                el.value = amountMajor;
            }
            const m = Math.min(Number(amountMinor) || 0, this.balanceMinor);
            this.draftRemainingMinor = Math.max(0, this.balanceMinor - m);
            const kind = document.getElementById('payment_kind');
            if (kind) {
                if (Number(percent) < 100) {
                    const dep = kind.querySelector('option[value="deposit"]');
                    if (dep) {
                        kind.value = 'deposit';
                    }
                } else {
                    const std = kind.querySelector('option[value="standard"]');
                    if (std) {
                        kind.value = 'standard';
                    }
                }
            }
        },
        init() {
            const el = document.getElementById('amount');
            if (el && el.value) {
                this.onAmountInput({ target: el });
            }
        },
    }));

    Alpine.data('portalInvoicePay', (cfg) => ({
        methods: Array.isArray(cfg.methods) ? cfg.methods : [],
        selectedMethod: Array.isArray(cfg.methods) && cfg.methods[0] ? cfg.methods[0].id : null,
        stripeUrl: cfg.stripeUrl || null,
        paypalUrl: cfg.paypalUrl || null,
        flouciUrl: cfg.flouciUrl || null,
        stripePk: cfg.stripePublishableKey || null,
        loading: false,
        err: '',
        stripeClientSecret: null,
        stripeInstance: null,
        stripeElements: null,
        showStripeForm: false,
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        async postJson(url) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || res.statusText || 'Request failed');
            }

            return data;
        },
        async payPayPal() {
            if (!this.paypalUrl) {
                return;
            }
            this.loading = true;
            this.err = '';
            try {
                const data = await this.postJson(this.paypalUrl);
                if (data.approval_url) {
                    window.location.href = data.approval_url;

                    return;
                }
                throw new Error('PayPal approval URL missing');
            } catch (e) {
                this.err = e.message || 'PayPal failed';
                this.loading = false;
            }
        },
        async payFlouci() {
            if (!this.flouciUrl) {
                return;
            }
            this.loading = true;
            this.err = '';
            try {
                const data = await this.postJson(this.flouciUrl);
                if (data.payment_url) {
                    window.location.href = data.payment_url;

                    return;
                }
                throw new Error('Flouci payment URL missing');
            } catch (e) {
                this.err = e.message || 'Flouci failed';
                this.loading = false;
            }
        },
        async initStripeForm() {
            if (!this.stripeUrl || !this.stripePk || typeof window.Stripe === 'undefined') {
                this.err = 'Stripe is not available.';

                return false;
            }
            if (this.stripeElements) {
                return true;
            }
            const data = await this.postJson(this.stripeUrl);
            const secret = data.client_secret;
            if (!secret) {
                throw new Error('Missing payment session');
            }
            this.stripeClientSecret = secret;
            this.stripeInstance = window.Stripe(this.stripePk);
            this.stripeElements = this.stripeInstance.elements({ clientSecret: secret });
            const paymentElement = this.stripeElements.create('payment');
            await this.$nextTick();
            const mount = this.$refs.paymentElement;
            if (!mount) {
                throw new Error('Payment form unavailable');
            }
            paymentElement.mount(mount);

            return true;
        },
        async payStripe() {
            this.loading = true;
            this.err = '';
            try {
                if (!this.showStripeForm) {
                    this.showStripeForm = true;
                    await this.$nextTick();
                    await this.initStripeForm();
                    this.loading = false;

                    return;
                }
                if (!this.stripeInstance || !this.stripeElements || !this.stripeClientSecret) {
                    await this.initStripeForm();
                }
                const returnBase = window.location.href.split('?')[0];
                const { error } = await this.stripeInstance.confirmPayment({
                    elements: this.stripeElements,
                    clientSecret: this.stripeClientSecret,
                    confirmParams: {
                        return_url: `${returnBase}?payment=success&provider=stripe`,
                    },
                });
                if (error) {
                    this.err = error.message || 'Payment failed';
                }
            } catch (e) {
                this.err = e.message || 'Stripe failed';
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('flowdeskMessenger', (cfg) => ({
        cfg,
        open: false,
        booted: false,
        loading: false,
        sending: false,
        view: 'list',
        threads: [],
        selfId: '',
        fullPageUrl: '',
        threadTitle: '',
        activeThreadId: null,
        messages: [],
        lastId: 0,
        body: '',
        pollTimer: null,
        pollIntervalMs: 2500,
        pollFailStreak: 0,
        _pollVisHandler: null,

        typeLabel(type) {
            return this.cfg.labels?.[type] || type || '';
        },

        toggle() {
            this.open = !this.open;
            if (this.open && !this.booted) {
                this.bootstrap();
            }
            if (!this.open) {
                this.stopPoll();
            }
        },

        async bootstrap() {
            this.loading = true;
            try {
                const r = await fetch(this.cfg.bootstrapUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const d = await r.json().catch(() => ({}));
                this.threads = Array.isArray(d.threads) ? d.threads : [];
                this.selfId = String(d.self_id || '');
                this.fullPageUrl = d.full_page_url || '';
                this.booted = true;
                if (this.threads.length === 1) {
                    const t = this.threads[0];
                    await this.openThread(t.id, t.label);
                }
            } finally {
                this.loading = false;
            }
        },

        backToList() {
            this.stopPoll();
            this.view = 'list';
            this.activeThreadId = null;
            this.threadTitle = '';
            this.messages = [];
            this.lastId = 0;
            this.body = '';
        },

        messagesFullUrl(id) {
            return `${this.cfg.chatBase}/${id}/messages/full`;
        },

        pollUrl(id) {
            return `${this.cfg.chatBase}/${id}/messages/poll`;
        },

        storeUrl(id) {
            return `${this.cfg.chatBase}/${id}/messages`;
        },

        async openThread(id, label) {
            this.activeThreadId = id;
            this.threadTitle = label || '';
            this.view = 'thread';
            this.loading = true;
            this.messages = [];
            this.lastId = 0;
            try {
                const r = await fetch(this.messagesFullUrl(id), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const d = await r.json().catch(() => ({}));
                const list = Array.isArray(d.messages) ? d.messages : [];
                list.forEach((m) => this.appendMessage(m));
                list.forEach((m) => {
                    if (m.id > this.lastId) {
                        this.lastId = m.id;
                    }
                });
            } finally {
                this.loading = false;
            }
            this.$nextTick(() => this.scrollStream());
            this.startPoll();
        },

        appendMessage(m) {
            if (this.messages.some((x) => x.id === m.id)) {
                return;
            }
            this.messages.push(m);
        },

        startPoll() {
            this.stopPoll();
            if (!this.activeThreadId) {
                return;
            }
            const id = this.activeThreadId;
            this.pollIntervalMs = 2500;
            this.pollFailStreak = 0;

            this._pollVisHandler = () => {
                if (!document.hidden && this.activeThreadId === id) {
                    void this.runPollTick(id);
                }
            };
            document.addEventListener('visibilitychange', this._pollVisHandler);

            const scheduleNext = () => {
                this.pollTimer = setTimeout(async () => {
                    if (this.activeThreadId !== id) {
                        return;
                    }
                    if (!document.hidden) {
                        await this.runPollTick(id);
                    }
                    scheduleNext();
                }, this.pollIntervalMs);
            };
            scheduleNext();
        },

        async runPollTick(id) {
            try {
                const r = await fetch(`${this.pollUrl(id)}?after=${this.lastId}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) {
                    this.pollFailStreak++;
                    this.pollIntervalMs = Math.min(30000, 2500 * 2 ** Math.min(this.pollFailStreak, 4));
                    return;
                }
                this.pollFailStreak = 0;
                this.pollIntervalMs = 2500;
                const d = await r.json().catch(() => ({}));
                const list = Array.isArray(d.messages) ? d.messages : [];
                for (const m of list) {
                    if (m.id > this.lastId) {
                        this.lastId = m.id;
                        this.appendMessage(m);
                    }
                }
                this.$nextTick(() => this.scrollStream());
            } catch (e) {
                this.pollFailStreak++;
                this.pollIntervalMs = Math.min(30000, 2500 * 2 ** Math.min(this.pollFailStreak, 4));
            }
        },

        stopPoll() {
            if (this._pollVisHandler) {
                document.removeEventListener('visibilitychange', this._pollVisHandler);
                this._pollVisHandler = null;
            }
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
            }
            this.pollTimer = null;
        },

        scrollStream() {
            const el = this.$refs.stream;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },

        formatTime(iso) {
            if (!iso) {
                return '';
            }
            const dt = new Date(iso);
            if (Number.isNaN(dt.getTime())) {
                return '';
            }
            const pad = (n) => String(n).padStart(2, '0');
            return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
        },

        async send() {
            const text = (this.body || '').trim();
            if (!text || !this.activeThreadId || this.sending) {
                return;
            }
            const id = this.activeThreadId;
            this.sending = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const r = await fetch(this.storeUrl(id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ body: text }),
                    credentials: 'same-origin',
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok) {
                    if (d.message) {
                        this.appendMessage(d.message);
                        if (d.message.id > this.lastId) {
                            this.lastId = d.message.id;
                        }
                    }
                    this.body = '';
                    this.$nextTick(() => this.scrollStream());
                }
            } finally {
                this.sending = false;
            }
        },
    }));

window.flowdeskSetEmailBodyHtml = (bodyId, html, options = {}) => {
    window.dispatchEvent(
        new CustomEvent('flowdesk-email-body-html', {
            detail: {
                bodyId: bodyId || 'body_html',
                html: html != null ? String(html) : '',
                showPreview: Boolean(options.showPreview),
            },
        }),
    );
};

    /** Email marketing / template HTML: insert merge tags, preview body in modal */
    Alpine.data('emailHtmlEditorTools', (cfg) => {
        const bodyId = (cfg && cfg.bodyId) || 'body_html';
        const subjectId = (cfg && cfg.subjectId) || 'subject';
        const bodyDisabled = Boolean(cfg && cfg.bodyDisabled);
        const previewEmailUrl = (cfg && cfg.previewEmailUrl) || null;
        const previewCampaignId = (cfg && cfg.previewCampaignId) || null;

        return {
            bodyId,
            subjectId,
            bodyDisabled,
            previewEmailUrl,
            previewCampaignId,
            previewEmailTo: (cfg && cfg.previewEmailTo) || '',
            companyLogoUrl: (cfg && cfg.companyLogoUrl) || '',
            companyName: (cfg && cfg.companyName) || '',
            toolTextColor: (cfg && cfg.brandPrimary) || '#4f46e5',
            toolBgColor: '#ffffff',
            linkPromptLabel: (cfg && cfg.linkPromptLabel) || 'URL',
            previewEmailBusy: false,
            previewEmailErr: null,
            previewEmailOk: null,
            previewOpen: false,
            viewMode: 'code',
            srcdoc: '',
            /** Set when textarea value is pushed from the preview iframe; skips updating srcdoc so the iframe is not reloaded. */
            ignoreTextareaInput: false,
            previewEditDebounce: null,
            /** Last caret in the code textarea (buttons steal focus before click). */
            savedTextareaCaret: null,
            /** Last caret inside the preview iframe. */
            iframeSavedRange: null,
            expandMergeTagsForPreview(html) {
                let s = String(html || '');
                if (this.companyLogoUrl) {
                    s = s.split('{{company_logo}}').join(this.companyLogoUrl);
                }
                return s;
            },
            collapseMergeTagsFromPreview(html) {
                let s = String(html || '');
                if (this.companyLogoUrl) {
                    s = s.split(this.companyLogoUrl).join('{{company_logo}}');
                }
                return s;
            },
            logoBlockHtml(align) {
                const a = align === 'left' ? 'left' : align === 'right' ? 'right' : 'center';
                return `<div style="text-align:${a};padding:12px 0;margin:8px 0"><img src="{{company_logo}}" alt="{{company_name}}" style="max-height:72px;max-width:220px;height:auto;display:inline-block;border:0" /></div>`;
            },
            applyHtml(html, showPreview = false) {
                const ta = document.getElementById(this.bodyId);
                if (!ta) {
                    return;
                }
                const value = html != null ? String(html) : '';
                ta.value = value;
                this.srcdoc = this.expandMergeTagsForPreview(value);
                if (showPreview && value !== '') {
                    this.setViewMode('preview');
                }
            },
            setViewMode(mode) {
                if (this.viewMode === 'preview' && mode === 'code') {
                    this.syncPreviewHtmlFromIframe();
                }
                this.viewMode = mode;
                if (mode === 'preview') {
                    this.iframeSavedRange = null;
                    const ta = document.getElementById(this.bodyId);
                    const raw = ta && ta.value != null ? String(ta.value) : '';
                    this.srcdoc = this.expandMergeTagsForPreview(raw);
                }
            },
            saveTextareaCaret() {
                const ta = document.getElementById(this.bodyId);
                if (!ta || typeof ta.selectionStart !== 'number') {
                    return;
                }
                this.savedTextareaCaret = {
                    start: ta.selectionStart,
                    end: ta.selectionEnd,
                };
            },
            saveIframeSelection(iframe) {
                if (!(iframe instanceof HTMLIFrameElement) || !iframe.contentWindow) {
                    return;
                }
                const sel = iframe.contentWindow.getSelection();
                if (!sel || sel.rangeCount < 1) {
                    return;
                }
                try {
                    this.iframeSavedRange = sel.getRangeAt(0).cloneRange();
                } catch (e) {
                    this.iframeSavedRange = null;
                }
            },
            serializeIframeDocument(doc) {
                if (!doc || !doc.documentElement) {
                    return '';
                }
                const d = doc.doctype;
                const prolog = d ? `<!DOCTYPE ${d.name}>\n` : '<!DOCTYPE html>\n';
                return prolog + doc.documentElement.outerHTML;
            },
            syncPreviewHtmlFromIframe() {
                if (this.viewMode !== 'preview' || this.bodyDisabled) {
                    return;
                }
                const iframe = this.$refs?.previewFrame;
                const win = iframe?.contentWindow;
                if (!win?.document?.documentElement) {
                    return;
                }
                const html = this.collapseMergeTagsFromPreview(
                    this.serializeIframeDocument(win.document),
                );
                if (!html) {
                    return;
                }
                const ta = document.getElementById(this.bodyId);
                if (!ta) {
                    return;
                }
                this.ignoreTextareaInput = true;
                ta.value = html;
            },
            debouncedSyncFromIframe() {
                if (this.previewEditDebounce) {
                    clearTimeout(this.previewEditDebounce);
                }
                this.previewEditDebounce = setTimeout(() => {
                    this.previewEditDebounce = null;
                    this.syncPreviewHtmlFromIframe();
                }, 200);
            },
            execPreviewCommand(command, value) {
                const iframe = this.$refs?.previewFrame;
                const win = iframe?.contentWindow;
                const doc = win?.document;
                if (!win || !doc?.body) {
                    return;
                }
                win.focus();
                try {
                    doc.execCommand(command, false, value ?? null);
                } catch (e) {
                    return;
                }
                this.debouncedSyncFromIframe();
            },
            insertPreviewLink() {
                const url = window.prompt(this.linkPromptLabel, 'https://');
                if (!url || !String(url).trim()) {
                    return;
                }
                this.execPreviewCommand('createLink', String(url).trim());
            },
            insertLogoBlock(align) {
                this.insertHtmlAtCursor(this.logoBlockHtml(align));
            },
            onPreviewFrameLoad(iframe) {
                if (this.viewMode !== 'preview' || this.bodyDisabled) {
                    return;
                }
                if (!(iframe instanceof HTMLIFrameElement) || !iframe.contentWindow) {
                    return;
                }
                const doc = iframe.contentWindow.document;
                if (!doc?.body) {
                    return;
                }
                try {
                    doc.designMode = 'on';
                } catch (e) {
                    return;
                }
                const track = () => this.saveIframeSelection(iframe);
                doc.addEventListener('mouseup', track);
                doc.addEventListener('keyup', track);
                doc.addEventListener('focus', track, true);
                const onEdit = () => {
                    if (this.viewMode !== 'preview' || this.bodyDisabled) {
                        return;
                    }
                    this.debouncedSyncFromIframe();
                };
                doc.addEventListener('input', onEdit);
                doc.addEventListener('paste', onEdit);
            },
            syncPreviewFromTextarea() {
                if (this.viewMode !== 'preview') {
                    return;
                }
                if (this.ignoreTextareaInput) {
                    this.ignoreTextareaInput = false;
                    return;
                }
                const ta = document.getElementById(this.bodyId);
                const raw = ta && ta.value != null ? String(ta.value) : '';
                this.srcdoc = this.expandMergeTagsForPreview(raw);
            },
            openPreview() {
                const ta = document.getElementById(this.bodyId);
                const raw = ta && ta.value != null ? String(ta.value) : '';
                this.srcdoc = this.expandMergeTagsForPreview(raw);
                this.previewOpen = true;
            },
            closePreview() {
                this.previewOpen = false;
            },
            insertHtmlMergeTagInBody(ta, ins) {
                const v = String(ta.value || '');
                const low = v.toLowerCase();
                const bodyEnd = low.lastIndexOf('</body>');
                if (bodyEnd !== -1) {
                    return v.slice(0, bodyEnd) + ins + v.slice(bodyEnd);
                }
                const htmlEnd = low.lastIndexOf('</html>');
                if (htmlEnd !== -1) {
                    return v.slice(0, htmlEnd) + ins + v.slice(htmlEnd);
                }
                return v + ins;
            },
            insertHtmlAtIframeCursor(html) {
                const iframe = this.$refs?.previewFrame;
                const win = iframe?.contentWindow;
                const doc = win?.document;
                if (!doc?.body) {
                    return false;
                }
                const ins = String(html || '');
                const sel = win.getSelection();
                let range = null;
                if (this.iframeSavedRange) {
                    try {
                        range = this.iframeSavedRange.cloneRange();
                    } catch (e) {
                        range = null;
                    }
                }
                if (!range && sel && sel.rangeCount > 0) {
                    range = sel.getRangeAt(0);
                }
                if (!range) {
                    return false;
                }
                try {
                    if (sel) {
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                    const fragment = range.createContextualFragment(
                        this.expandMergeTagsForPreview(ins),
                    );
                    range.deleteContents();
                    range.insertNode(fragment);
                    range.collapse(false);
                    if (sel) {
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                    this.iframeSavedRange = range.cloneRange();
                } catch (e) {
                    return false;
                }
                this.syncPreviewHtmlFromIframe();
                return true;
            },
            insertHtmlAtCursor(html) {
                const ta = document.getElementById(this.bodyId);
                if (!ta) {
                    return;
                }
                const ins = String(html || '');
                if (this.viewMode === 'preview') {
                    if (!this.insertHtmlAtIframeCursor(ins)) {
                        const v = this.insertHtmlMergeTagInBody(ta, ins);
                        this.ignoreTextareaInput = true;
                        ta.value = v;
                        this.srcdoc = this.expandMergeTagsForPreview(v);
                    }
                    return;
                }
                const v = String(ta.value || '');
                let start = v.length;
                let end = v.length;
                if (
                    this.savedTextareaCaret
                    && Number.isInteger(this.savedTextareaCaret.start)
                    && Number.isInteger(this.savedTextareaCaret.end)
                ) {
                    start = this.savedTextareaCaret.start;
                    end = this.savedTextareaCaret.end;
                } else if (Number.isInteger(ta.selectionStart)) {
                    start = ta.selectionStart;
                    end = Number.isInteger(ta.selectionEnd) ? ta.selectionEnd : start;
                }
                ta.value = v.slice(0, start) + ins + v.slice(end);
                const pos = start + ins.length;
                this.savedTextareaCaret = { start: pos, end: pos };
                ta.focus();
                if (typeof ta.setSelectionRange === 'function') {
                    ta.setSelectionRange(pos, pos);
                }
            },
            insertAtCursor(tag) {
                const id = this.bodyId;
                const ta = document.getElementById(id);
                if (!ta) {
                    return;
                }
                const ins = String(tag || '');
                if (this.viewMode === 'preview') {
                    if (!this.insertHtmlAtIframeCursor(ins)) {
                        const v = this.insertHtmlMergeTagInBody(ta, ins);
                        this.ignoreTextareaInput = true;
                        ta.value = v;
                        this.srcdoc = this.expandMergeTagsForPreview(v);
                    }
                    return;
                }
                const v = String(ta.value || '');
                let start = v.length;
                let end = v.length;
                if (
                    this.savedTextareaCaret
                    && Number.isInteger(this.savedTextareaCaret.start)
                    && Number.isInteger(this.savedTextareaCaret.end)
                ) {
                    start = this.savedTextareaCaret.start;
                    end = this.savedTextareaCaret.end;
                } else if (Number.isInteger(ta.selectionStart)) {
                    start = ta.selectionStart;
                    end = Number.isInteger(ta.selectionEnd) ? ta.selectionEnd : start;
                }
                ta.value = v.slice(0, start) + ins + v.slice(end);
                const pos = start + ins.length;
                this.savedTextareaCaret = { start: pos, end: pos };
                ta.focus();
                if (typeof ta.setSelectionRange === 'function') {
                    ta.setSelectionRange(pos, pos);
                }
            },
            savedSubjectCaret: null,
            saveSubjectCaret() {
                const ta = document.getElementById(this.subjectId);
                if (!ta || typeof ta.selectionStart !== 'number') {
                    return;
                }
                this.savedSubjectCaret = {
                    start: ta.selectionStart,
                    end: ta.selectionEnd,
                };
            },
            insertInSubject(tag) {
                const ta = document.getElementById(this.subjectId);
                if (!ta) {
                    return;
                }
                const v = String(ta.value || '');
                const ins = String(tag || '');
                let start = v.length;
                let end = v.length;
                if (
                    this.savedSubjectCaret
                    && Number.isInteger(this.savedSubjectCaret.start)
                    && Number.isInteger(this.savedSubjectCaret.end)
                ) {
                    start = this.savedSubjectCaret.start;
                    end = this.savedSubjectCaret.end;
                } else if (Number.isInteger(ta.selectionStart)) {
                    start = ta.selectionStart;
                    end = Number.isInteger(ta.selectionEnd) ? ta.selectionEnd : start;
                }
                ta.value = v.slice(0, start) + ins + v.slice(end);
                const pos = start + ins.length;
                this.savedSubjectCaret = { start: pos, end: pos };
                ta.focus();
                if (typeof ta.setSelectionRange === 'function') {
                    ta.setSelectionRange(pos, pos);
                }
            },
            collectEditorHtml() {
                if (this.viewMode === 'preview') {
                    this.syncPreviewHtmlFromIframe();
                }
                const ta = document.getElementById(this.bodyId);
                return ta && ta.value != null ? String(ta.value) : '';
            },
            async sendPreviewEmail() {
                if (!this.previewEmailUrl || this.bodyDisabled) {
                    return;
                }
                this.previewEmailErr = null;
                this.previewEmailOk = null;
                const to = String(this.previewEmailTo || '').trim();
                if (!to) {
                    this.previewEmailErr = 'Enter a valid email address.';
                    return;
                }
                const bodyHtml = this.collectEditorHtml();
                if (!bodyHtml.trim()) {
                    this.previewEmailErr = 'Message body is empty.';
                    return;
                }
                const subjectEl = document.getElementById(this.subjectId);
                const subject = subjectEl ? String(subjectEl.value || '') : '';
                const token = (document.querySelector('meta[name="csrf-token"]') || {}).content;
                if (!token) {
                    this.previewEmailErr = 'Request failed.';
                    return;
                }
                this.previewEmailBusy = true;
                try {
                    const res = await fetch(this.previewEmailUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            sample_to: to,
                            subject,
                            body_html: bodyHtml,
                            campaign_id: this.previewCampaignId || null,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.previewEmailErr = data.message || 'Could not send preview.';
                        return;
                    }
                    this.previewEmailOk = data.message || 'Preview sent.';
                } catch (e) {
                    this.previewEmailErr = e && e.message ? e.message : 'Could not send preview.';
                } finally {
                    this.previewEmailBusy = false;
                }
            },
            init() {
                const el = this.$el;
                if (!(el instanceof HTMLElement)) {
                    return;
                }
                const ta = document.getElementById(this.bodyId);
                if (ta) {
                    const trackBody = () => this.saveTextareaCaret();
                    ta.addEventListener('select', trackBody);
                    ta.addEventListener('keyup', trackBody);
                    ta.addEventListener('mouseup', trackBody);
                    ta.addEventListener('focus', trackBody);
                }
                const subjectTa = document.getElementById(this.subjectId);
                if (subjectTa) {
                    const trackSubject = () => this.saveSubjectCaret();
                    subjectTa.addEventListener('select', trackSubject);
                    subjectTa.addEventListener('keyup', trackSubject);
                    subjectTa.addEventListener('mouseup', trackSubject);
                    subjectTa.addEventListener('focus', trackSubject);
                }
                if (ta && String(ta.value || '').trim() !== '') {
                    this.setViewMode('preview');
                }
                const onBodySet = (event) => {
                    const detail = event && event.detail ? event.detail : {};
                    if (detail.bodyId && detail.bodyId !== this.bodyId) {
                        return;
                    }
                    this.applyHtml(detail.html ?? '', Boolean(detail.showPreview));
                };
                window.addEventListener('flowdesk-email-body-html', onBodySet);
                const form = el.closest('form');
                if (!form) {
                    return;
                }
                form.addEventListener(
                    'submit',
                    () => {
                        this.setViewMode('code');
                    },
                    true,
                );
            },
        };
    });

    /**
     * Audience contacts textarea: "sync clients" button merges the workspace's
     * client emails into the list without duplicating existing lines.
     */
    Alpine.data('flowdeskAudienceContacts', (cfg = {}) => ({
        clientEmails: Array.isArray(cfg.emails) ? cfg.emails : [],
        added: null,

        syncClients() {
            const el = document.getElementById('contacts_input');
            if (!el) {
                return;
            }

            const lines = el.value
                .split(/\r\n|\r|\n/)
                .map((line) => line.trim())
                .filter(Boolean);
            const existing = new Set(lines.map((line) => line.toLowerCase()));

            let count = 0;
            for (const email of this.clientEmails) {
                const normalized = String(email).trim().toLowerCase();
                if (normalized && !existing.has(normalized)) {
                    existing.add(normalized);
                    lines.push(normalized);
                    count++;
                }
            }

            el.value = lines.join('\n');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            this.added = count;
        },

        addedMessage() {
            if (this.added === null) {
                return '';
            }

            const tpl = this.added > 0
                ? (cfg.addedMessage || ':count contact(s) added from clients.')
                : (cfg.noneAddedMessage || 'All client emails are already in the list.');

            return tpl.replace(':count', String(this.added));
        },
    }));

    /**
     * Email template starter: theme list (sortable) + color / border / radius, live HTML preview.
     */
    Alpine.data('emailMarketingStarterModal', (cfg) => {
        const presets = (cfg && cfg.presets) || [];
        const firstId = presets[0]?.id ?? 'indigo';

        return {
            open: false,
            fieldId: (cfg && cfg.fieldId) || 'body_html',
            baseTemplate: (cfg && cfg.baseTemplate) || '',
            presets,
            selectedId: firstId,
            borderW: '1px',
            cardR: '16px',
            custom: {},
            sortable: null,
            get selectedPreset() {
                return this.presets.find((p) => p.id === this.selectedId);
            },
            get mergedTokens() {
                const s = this.selectedPreset;
                if (!s) {
                    return {};
                }
                return {
                    ...s.tokens,
                    '%%CARD_BORDER_W%%': this.borderW,
                    '%%CARD_R%%': this.cardR,
                    ...this.custom,
                };
            },
            get html() {
                let out = this.baseTemplate;
                const m = this.mergedTokens;
                const keys = Object.keys(m).sort((a, b) => b.length - a.length);
                keys.forEach((k) => {
                    const v = m[k];
                    if (v !== null && v !== undefined) {
                        out = out.split(k).join(String(v));
                    }
                });
                return out;
            },
            normalizeHex(v) {
                const s = String(v || '').trim();
                if (/^#[0-9A-Fa-f]{6}$/.test(s)) {
                    return s;
                }
                if (/^[0-9A-Fa-f]{6}$/.test(s)) {
                    return `#${s}`;
                }
                return '';
            },
            colorForRole(role) {
                const m = this.mergedTokens;
                if (role === 'accent') {
                    return m['%%CTA%%'] || '#4f46e5';
                }
                if (role === 'outer') {
                    return m['%%OUTER_BG%%'] || '#f1f5f9';
                }
                if (role === 'card') {
                    return m['%%CARD_BG%%'] || '#ffffff';
                }
                if (role === 'border') {
                    return m['%%CARD_BORDER%%'] || '#e2e8f0';
                }
                if (role === 'heading') {
                    return m['%%H1%%'] || '#0f172a';
                }
                return '#000000';
            },
            setAccent(ev) {
                const h = this.normalizeHex(ev?.target?.value);
                if (!h) {
                    return;
                }
                const noHash = h.replace('#', '');
                this.custom = {
                    ...this.custom,
                    '%%CTA%%': h,
                    '%%CTA_BORDER%%': h,
                    '%%BAR%%': h,
                    '%%LINK%%': h,
                    '%%LOGO1%%': noHash,
                    '%%BAR2%%': h,
                };
            },
            setOuterBg(ev) {
                const h = this.normalizeHex(ev?.target?.value);
                if (h) {
                    this.custom = { ...this.custom, '%%OUTER_BG%%': h };
                }
            },
            setCardBg(ev) {
                const h = this.normalizeHex(ev?.target?.value);
                if (h) {
                    this.custom = { ...this.custom, '%%CARD_BG%%': h };
                }
            },
            setBorderColor(ev) {
                const h = this.normalizeHex(ev?.target?.value);
                if (h) {
                    this.custom = { ...this.custom, '%%CARD_BORDER%%': h, '%%RULE%%': h };
                }
            },
            setHeadingColor(ev) {
                const h = this.normalizeHex(ev?.target?.value);
                if (h) {
                    this.custom = { ...this.custom, '%%H1%%': h };
                }
            },
            selectPreset(id) {
                this.selectedId = id;
                this.custom = {};
                const p = this.presets.find((x) => x.id === id);
                if (p?.tokens) {
                    this.borderW = p.tokens['%%CARD_BORDER_W%%'] || '1px';
                    this.cardR = p.tokens['%%CARD_R%%'] || '16px';
                }
            },
            resetThemeTweaks() {
                this.custom = {};
                this.selectPreset(this.selectedId);
            },
            openModal() {
                this.open = true;
            },
            apply() {
                const el = document.getElementById(this.fieldId);
                if (el) {
                    el.value = this.html;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
                this.open = false;
            },
            init() {
                this.selectPreset(this.selectedId);
                this.$nextTick(() => {
                    const root = this.$refs.presetList;
                    if (!root || !Sortable) {
                        return;
                    }
                    this.sortable = Sortable.create(root, {
                        animation: 180,
                        handle: '[data-preset-handle]',
                        draggable: '[data-preset-item]',
                        ghostClass: 'flowdesk-starter-ghost',
                    });
                });
            },
        };
    });

    /**
     * Project form: “New client” modal + quick create (avoids x-data in HTML: regex with `>` breaks attribute parsing).
     */
    Alpine.data('projectClientQuickAdd', (cfg) => {
        const t = (cfg && cfg.i18n) || {};
        return {
            modalOpen: false,
            busy: false,
            err: null,
            quickUrl: (cfg && cfg.quickUrl) || '',
            selectId: (cfg && cfg.selectId) || 'client_id',
            csrf: (cfg && cfg.csrf) || '',
            async quickSubmit() {
                const nameEl = document.getElementById('qc_name');
                const name = (nameEl && nameEl.value ? nameEl.value : '').trim();
                if (!name) {
                    this.err = t.required || '';
                    return;
                }
                this.busy = true;
                this.err = null;
                const fd = new FormData();
                fd.append('name', name);
                const em = document.getElementById('qc_email');
                const ph = document.getElementById('qc_phone');
                fd.append('email', (em && em.value) || '');
                fd.append('phone', (ph && ph.value) || '');
                fd.append('_token', this.csrf);
                try {
                    const r = await fetch(this.quickUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrf,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                        credentials: 'same-origin',
                    });
                    const text = await r.text();
                    let body = {};
                    try {
                        body = text ? JSON.parse(text) : {};
                    } catch (parseErr) {
                        const re = new RegExp('<[^>]+>', 'g');
                        body = { message: text ? text.replace(re, ' ').trim().slice(0, 200) : '' };
                    }
                    this.busy = false;
                    if (!r.ok) {
                        if (r.status === 419) {
                            this.err = t.pageExpired || '';
                        } else if (r.status === 403) {
                            this.err = body.message || t.notAllowed || '';
                        } else {
                            this.err =
                                body.message ||
                                (body.errors && Object.values(body.errors).flat().join(' ')) ||
                                (t.couldNot || '') + (r.status ? ' (' + r.status + ')' : '');
                        }
                        return;
                    }
                    const sel = document.getElementById(this.selectId);
                    if (sel && body.client) {
                        const opt = document.createElement('option');
                        opt.value = body.client.id;
                        opt.textContent = body.client.name + (body.client.code ? ' (' + body.client.code + ')' : '');
                        opt.selected = true;
                        sel.appendChild(opt);
                    }
                    this.modalOpen = false;
                    if (nameEl) {
                        nameEl.value = '';
                    }
                    if (em) {
                        em.value = '';
                    }
                    if (ph) {
                        ph.value = '';
                    }
                } catch (e) {
                    this.busy = false;
                    this.err = t.network || '';
                }
            },
        };
    });
});

Alpine.start();

const vueRoot = document.getElementById('flowdesk-vue-root');
if (vueRoot) {
    const appName = vueRoot.dataset.appName ?? 'Flowdesk';
    createApp(FlowdeskPulse, { appName }).mount(vueRoot);
}

// Color picker ↔ HEX input sync
(() => {
    function normalizeHex(value) {
        if (!value) return '';
        const v = String(value).trim();
        return /^#[0-9A-Fa-f]{6}$/.test(v) ? v : '';
    }

    function setupColorHexSync(root = document) {
        const boundKey = '__flowdeskColorHexBound';
        const bound = root[boundKey] instanceof WeakSet ? root[boundKey] : new WeakSet();
        root[boundKey] = bound;

        root.querySelectorAll('input[type="color"][data-sync-hex]').forEach((colorEl) => {
            if (bound.has(colorEl)) return;
            bound.add(colorEl);

            const selector = colorEl.getAttribute('data-sync-hex');
            const hexEl = selector ? document.querySelector(selector) : null;
            if (!hexEl) return;

            // init picker from hex (if valid)
            colorEl.value = normalizeHex(hexEl.value) || colorEl.value || '#000000';

            colorEl.addEventListener('input', () => {
                hexEl.value = colorEl.value;
                hexEl.dispatchEvent(new Event('input', { bubbles: true }));
                hexEl.dispatchEvent(new Event('change', { bubbles: true }));
            });

            hexEl.addEventListener('input', () => {
                const n = normalizeHex(hexEl.value);
                if (n) colorEl.value = n;
            });
        });
    }

    function boot() {
        setupColorHexSync(document);

        // Re-bind when Alpine renders template content.
        document.addEventListener('alpine:init', () => {
            queueMicrotask(() => setupColorHexSync(document));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();

/** When a theme template is selected, copy preset hex colors into inputs if “Apply preset colors” is checked. */
function setupAppearancePresetColorSync() {
    const form = document.querySelector('form[action*="/settings/appearance"]');
    if (!form) {
        return;
    }

    const applyPreset = form.querySelector('#apply_preset_colors');
    const primaryHex = form.querySelector('#primary_color');
    const secondaryHex = form.querySelector('#secondary_color');
    if (!primaryHex || !secondaryHex) {
        return;
    }

    function syncFromSelectedTemplate() {
        if (applyPreset && !applyPreset.checked) {
            return;
        }
        const radio = form.querySelector('input[name="theme_name"]:checked');
        const label = radio?.closest('label');
        const pri = label?.dataset?.flowdeskPresetPrimary;
        const sec = label?.dataset?.flowdeskPresetSecondary;
        if (pri && /^#[0-9A-Fa-f]{6}$/.test(pri)) {
            primaryHex.value = pri;
            primaryHex.dispatchEvent(new Event('input', { bubbles: true }));
            primaryHex.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (sec && /^#[0-9A-Fa-f]{6}$/.test(sec)) {
            secondaryHex.value = sec;
            secondaryHex.dispatchEvent(new Event('input', { bubbles: true }));
            secondaryHex.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    form.querySelectorAll('input[name="theme_name"]').forEach((el) => {
        el.addEventListener('change', syncFromSelectedTemplate);
    });
    applyPreset?.addEventListener('change', () => {
        if (applyPreset.checked) {
            syncFromSelectedTemplate();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupAppearancePresetColorSync, { once: true });
} else {
    setupAppearancePresetColorSync();
}

/**
 * Project / money fields: only digits and one decimal separator (`.` or `,`). Strips all letters and symbols.
 * Backend accepts comma or dot (see flowdesk_decimal_to_minor).
 */
function flowdeskSanitizeAmountInputValue(raw) {
    const s = String(raw).replace(/[^\d.,]/g, '');
    const i = s.search(/[.,]/);
    if (i === -1) {
        return s;
    }
    const intPart = s.slice(0, i).replace(/[^\d]/g, '');
    const sep = s[i];
    const onlyDigits = s
        .slice(i + 1)
        .replace(/[.,]/g, '')
        .replace(/[^\d]/g, '');
    if (onlyDigits.length === 0 && /[.,]$/.test(s)) {
        return intPart + sep;
    }
    return intPart + sep + onlyDigits;
}

function setupFlowdeskAmountInputs() {
    document.addEventListener(
        'input',
        (e) => {
            const el = e.target;
            if (!el || el.nodeName !== 'INPUT' || !el.classList || !el.classList.contains('flowdesk-amount')) {
                return;
            }
            const next = flowdeskSanitizeAmountInputValue(el.value);
            if (next !== el.value) {
                el.value = next;
            }
        },
        true,
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupFlowdeskAmountInputs, { once: true });
} else {
    setupFlowdeskAmountInputs();
}
