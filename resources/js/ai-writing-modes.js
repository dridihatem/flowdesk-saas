/**
 * Classic AI writing modes on /assistant (proposal, pricing, email, …).
 */

import { flowdeskSpeakNovaText } from './flowdesk-nova-speech';

function stripAiHtmlFences(text) {
    let s = String(text || '').trim();
    const fenced = s.match(/^```(?:html)?\s*([\s\S]*?)```$/i);
    if (fenced) {
        return fenced[1].trim();
    }

    return s.replace(/^```html\s*/i, '').replace(/```\s*$/i, '').trim();
}

function sanitizeLandingHtml(html) {
    return String(html || '')
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
        .replace(/\s+on\w+\s*=\s*(['"])[^'"]*\1/gi, '')
        .replace(/\s+on\w+\s*=\s*[^\s>]+/gi, '');
}

export function registerAiWritingModes(Alpine) {
    Alpine.data('aiWritingModes', (cfg = {}) => ({
        suggestUrl: cfg.suggestUrl || '',
        speakUrl: cfg.speakUrl || '',
        csrf: cfg.csrf || '',
        groups: cfg.groups || [],
        modes: cfg.modes || [],
        proposalClients: cfg.proposalClients || [],
        proposalQuoteDraftUrl: cfg.proposalQuoteDraftUrl || '',
        proposalPrefillUrl: cfg.proposalPrefillUrl || '',
        proposalClientContextUrl: cfg.proposalClientContextUrl || '',
        defaultCurrency: cfg.defaultCurrency || 'USD',
        selectedClientId: '',
        quoteName: '',
        quoteLineItems: [],
        quoteLinesBusy: false,
        quotePrefillBusy: false,
        speakBusy: false,
        selectedMode: cfg.initialMode || null,
        context: '',
        busy: false,
        error: '',
        result: '',
        resultTitle: '',
        landingHtml: '',
        landingTab: 'builder',
        _gjsEditor: null,
        _gjsInitTimer: null,
        _landingBuilderApi: null,

        init() {
            const hashMode = typeof window !== 'undefined' && window.location.hash.startsWith('#mode=')
                ? window.location.hash.slice(6)
                : null;
            const pick = hashMode && this.modes.find((m) => m.mode === hashMode);
            if (pick) {
                this.selectMode(pick);
            } else {
                if (hashMode && typeof window !== 'undefined' && window.history?.replaceState) {
                    const base = window.location.pathname + window.location.search;
                    const hash = window.location.hash.includes('#writing') ? '#writing' : '';
                    window.history.replaceState(null, '', `${base}${hash}`);
                }
                if (this.modes.length > 0 && !this.selectedMode) {
                    this.selectMode(this.modes[0]);
                }
            }

            const cleanup = () => this.destroyLandingBuilder();
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(cleanup);
            } else {
                this.$el.addEventListener('alpine:destroyed', cleanup, { once: true });
            }
        },

        selectMode(mod) {
            this.destroyLandingBuilder();
            this.selectedMode = mod.mode;
            this.context = mod.default_context || '';
            this.selectedClientId = '';
            this.quoteName = '';
            this.quoteLineItems = [];
            this.error = '';
            this.result = '';
            this.landingHtml = '';
            this.landingTab = 'builder';
            this.resultTitle = mod.title || '';
            if (typeof window !== 'undefined' && window.history?.replaceState) {
                const base = window.location.pathname + window.location.search;
                window.history.replaceState(null, '', `${base}#mode=${mod.mode}`);
            }
        },

        get activeMod() {
            return this.modes.find((m) => m.mode === this.selectedMode) || null;
        },

        get isLandingMode() {
            return this.activeMod?.mode === 'landing_page';
        },

        get isProposalMode() {
            return this.activeMod?.mode === 'proposal';
        },

        async applyClientContext() {
            if (!this.proposalClientContextUrl || !this.selectedClientId) {
                return;
            }

            try {
                const res = await fetch(this.proposalClientContextUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ client_id: this.selectedClientId }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || cfg.requestFailed || 'Request failed');
                }
                if (data.context) {
                    this.context = data.context;
                }
                const client = this.proposalClients.find((row) => row.id === this.selectedClientId);
                if (client && !this.quoteName) {
                    this.quoteName = `${client.name} — ${cfg.proposalQuoteNameFallback || 'Quote'}`;
                }
            } catch (e) {
                this.error = e?.message || cfg.requestFailed || 'Request failed';
            }
        },

        buildProposalBrief() {
            const parts = [(this.context || '').trim(), (this.result || '').trim()].filter(Boolean);
            return parts.join('\n\n');
        },

        async generateQuoteLines() {
            if (!this.proposalQuoteDraftUrl || this.quoteLinesBusy) {
                return;
            }

            const brief = this.buildProposalBrief();
            if (brief.length < 10) {
                this.error = cfg.quoteBriefRequired || 'Describe what to quote (min. 10 characters).';
                return;
            }

            this.quoteLinesBusy = true;
            this.error = '';

            try {
                const res = await fetch(this.proposalQuoteDraftUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        brief,
                        currency: this.defaultCurrency,
                        name: (this.quoteName || '').trim() || null,
                        client_id: this.selectedClientId || null,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || cfg.requestFailed || 'Request failed');
                }

                this.quoteLineItems = (data.items || []).map((row) => ({
                    description: row.description,
                    quantity: row.quantity,
                    unit_major: row.unit_major,
                }));
            } catch (e) {
                this.error = e?.message || cfg.requestFailed || 'Request failed';
            } finally {
                this.quoteLinesBusy = false;
            }
        },

        async openInQuote() {
            if (!this.proposalPrefillUrl || this.quotePrefillBusy || !this.result) {
                return;
            }

            this.quotePrefillBusy = true;
            this.error = '';

            try {
                const res = await fetch(this.proposalPrefillUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        client_id: this.selectedClientId || null,
                        outline: this.result,
                        quote_name: (this.quoteName || '').trim() || null,
                        items: this.quoteLineItems,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.redirect) {
                    throw new Error(data.message || cfg.requestFailed || 'Request failed');
                }
                window.location.href = data.redirect;
            } catch (e) {
                this.error = e?.message || cfg.requestFailed || 'Request failed';
                this.quotePrefillBusy = false;
            }
        },

        async speakResult() {
            const text = (this.result || '').trim();
            if (!text || !this.speakUrl || this.speakBusy) {
                return;
            }

            this.speakBusy = true;
            try {
                await flowdeskSpeakNovaText({
                    text,
                    speakUrl: this.speakUrl,
                    speechLocale: null,
                    synthesis: typeof window !== 'undefined' ? window.speechSynthesis : null,
                    browserFallbackHint: '',
                    onNotice: () => {},
                    onStart: () => {},
                    onEnd: () => {},
                });
            } finally {
                this.speakBusy = false;
            }
        },

        async generate() {
            const mod = this.activeMod;
            if (!mod || this.busy) {
                return;
            }

            const context = (this.context || '').trim();
            if (!context) {
                this.error = cfg.contextRequired || 'Add context before generating.';
                return;
            }

            this.busy = true;
            this.error = '';
            this.result = '';
            this.landingHtml = '';
            this.quoteLineItems = [];
            this.destroyLandingBuilder();
            this.resultTitle = mod.title;

            try {
                const res = await fetch(this.suggestUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ mode: mod.mode, context }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || cfg.requestFailed || 'Request failed');
                }

                const raw = data.suggestion || '';
                if (!raw) {
                    this.error = cfg.emptyResponse || 'Empty AI response.';
                    return;
                }

                if (mod.mode === 'landing_page') {
                    this.landingHtml = sanitizeLandingHtml(stripAiHtmlFences(raw));
                    this.result = this.landingHtml;
                    this.landingTab = 'builder';
                    this.$nextTick(() => this.initLandingBuilder());
                } else {
                    this.result = raw;
                }
            } catch (e) {
                this.error = e?.message || cfg.requestFailed || 'Request failed';
            } finally {
                this.busy = false;
            }
        },

        initLandingBuilder() {
            if (!this.isLandingMode || !this.landingHtml || this.landingTab !== 'builder') {
                return;
            }

            const container = this.$refs.gjsContainer;
            if (!container) {
                return;
            }

            this.destroyLandingBuilder();
            container.innerHTML = '';

            if (this._gjsInitTimer) {
                window.clearTimeout(this._gjsInitTimer);
            }

            const startBuilder = async () => {
                this._gjsInitTimer = null;
                const el = this.$refs.gjsContainer;
                if (!el || !this.isLandingMode || this.landingTab !== 'builder' || !this.landingHtml) {
                    return;
                }
                const api = await import('./landing-page-builder');
                this._landingBuilderApi = api;
                this._gjsEditor = api.createLandingBuilder(el, {
                    onUpdate: () => this.syncLandingFromBuilder(),
                    labels: cfg.landingBuilderLabels || {},
                });
                api.loadLandingHtml(this._gjsEditor, this.landingHtml);
            };

            const waitUntilVisible = (attempt = 0) => {
                const el = this.$refs.gjsContainer;
                const visible = el && el.offsetHeight > 80 && el.offsetParent !== null;
                if (visible || attempt > 40) {
                    this._gjsInitTimer = window.setTimeout(startBuilder, visible ? 0 : 120);
                    return;
                }
                requestAnimationFrame(() => waitUntilVisible(attempt + 1));
            };

            waitUntilVisible();
        },

        destroyLandingBuilder() {
            if (this._gjsInitTimer) {
                window.clearTimeout(this._gjsInitTimer);
                this._gjsInitTimer = null;
            }
            if (this._gjsEditor && this._landingBuilderApi) {
                this._landingBuilderApi.destroyLandingBuilder(this._gjsEditor);
            }
            this._gjsEditor = null;
            if (this.$refs.gjsContainer) {
                this.$refs.gjsContainer.innerHTML = '';
            }
        },

        setLandingTab(tab) {
            if (tab === 'code' && this.landingTab === 'builder') {
                this.syncLandingFromBuilder();
                this.destroyLandingBuilder();
            }
            this.landingTab = tab;
            if (tab === 'builder' && this.landingHtml) {
                this.$nextTick(() => this.initLandingBuilder());
            }
        },

        syncLandingFromBuilder() {
            if (!this._gjsEditor || !this._landingBuilderApi) {
                return;
            }
            this.landingHtml = this._landingBuilderApi.exportLandingHtml(this._gjsEditor);
            this.result = this.landingHtml;
        },

        syncLandingFromCode() {
            const sanitized = sanitizeLandingHtml(this.landingHtml);
            this.landingHtml = sanitized;
            this.result = sanitized;
        },

        async openLandingPreview() {
            if (this.landingTab === 'builder') {
                this.syncLandingFromBuilder();
            }
            const api = this._landingBuilderApi || (await import('./landing-page-builder'));
            const html = api.wrapLandingDocument(this.landingHtml);
            if (!html) {
                return;
            }
            const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (win) {
                win.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
            } else {
                URL.revokeObjectURL(url);
            }
        },

        copyResult() {
            if (this.landingTab === 'builder') {
                this.syncLandingFromBuilder();
            }
            const text = this.isLandingMode ? this.landingHtml : this.result;
            if (!text || !navigator.clipboard?.writeText) {
                return;
            }
            navigator.clipboard.writeText(text);
        },

        async downloadLandingHtml() {
            if (this.landingTab === 'builder') {
                this.syncLandingFromBuilder();
            }
            const api = this._landingBuilderApi || (await import('./landing-page-builder'));
            const html = api.wrapLandingDocument(this.landingHtml);
            if (!html) {
                return;
            }
            const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'landing-page.html';
            a.click();
            URL.revokeObjectURL(url);
        },
    }));
}
