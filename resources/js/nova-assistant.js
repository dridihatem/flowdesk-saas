import Alpine from 'alpinejs';
import {
    flowdeskApplyVoiceDictation,
    flowdeskIsVoiceLocaleSupported,
    flowdeskMatchesStopPhrase,
    flowdeskMatchesWakePhrase,
    flowdeskNormalizeVoicePhrase,
    flowdeskNotifyVoiceBusy,
    flowdeskNotifyVoiceIdle,
    flowdeskNovaStopPhrases,
    flowdeskSpeechLocale,
    flowdeskSpeechLocaleFallbacks,
    flowdeskVellisWakePhrases,
} from './ai-voice';
import { initNovaNeuralBackground } from './nova-neural-bg';
import { flowdeskIsolateRtlNumbers } from './flowdesk-numbers';
import { flowdeskFetchErrorMessage, flowdeskNotifyLabels, flowdeskSanitizeNotifyMessage } from './flowdesk-notify';
import { flowdeskSpeakNovaText, flowdeskStopNovaSpeech } from './flowdesk-nova-speech';

export function registerNovaAssistant(Alpine) {
    const novaAssistantFactory = (cfg = {}) => ({
        assistantName: cfg.assistantName || 'Nova',
        wakeBrand: cfg.wakeBrand || 'Nova',
        chatUrl: cfg.chatUrl || '',
        speakUrl: cfg.speakUrl || null,
        creditCost: cfg.creditCost || 0,
        csrf: cfg.csrf || '',
        appLocale: cfg.appLocale || 'en',
        isCompact: Boolean(cfg.compact),
        speechLocale: cfg.locale || flowdeskSpeechLocale(cfg.appLocale) || null,
        labels: cfg.labels || {},
        wakeHint: cfg.wakeHint || '',
        permissionError: cfg.permissionError || 'Microphone permission denied.',
        unsupportedError: cfg.unsupportedError || 'Voice not supported in this browser.',
        localeUnsupportedError: cfg.localeUnsupportedError || 'Voice is not available for this language.',
        state: 'idle',
        speaking: false,
        speakingText: '',
        wakeMode: false,
        transcript: '',
        draft: '',
        messages: [],
        conversationId: null,
        lastReply: '',
        error: '',
        voiceSupported: false,
        recognition: null,
        recognitionActive: false,
        wakePhrases: [],
        stopPhrases: [],
        synthesis: typeof window !== 'undefined' ? window.speechSynthesis : null,
        _neuralBg: null,
        _awaitingQuestion: false,
        _chatAbort: null,
        _listenForStop: false,
        enableWakeWord: false,

        get neuralEnergy() {
            if (this.speaking) {
                return 2;
            }
            if (this.state === 'listening') {
                return 1.8;
            }
            if (this.state === 'thinking' || this.state === 'responding') {
                return 1.4;
            }
            if (this.wakeMode) {
                return 1.1;
            }

            return 1;
        },

        get voiceActive() {
            return this.speaking || this.state === 'listening';
        },

        get cardState() {
            if (this.speaking) {
                return 'speaking';
            }

            return this.state;
        },

        get stateLabel() {
            if (this.speaking && this.speakingText) {
                if (this.isCompact) {
                    return this.labels.speaking || this.labels.responding || '';
                }

                return this.speakingText;
            }
            if (this.speaking) {
                return this.labels.speaking || this.labels.responding || '';
            }
            if (this.wakeMode && this.state === 'idle') {
                return this.labels.wake || this.labels.idle || '';
            }

            return this.labels[this.state] || this.labels.idle || '';
        },

        get statusBadgeLabel() {
            if (this.isCompact && this.speaking) {
                return this.labels.speaking || this.labels.responding || '';
            }

            return this.stateLabel;
        },

        get voiceUnavailableMessage() {
            if (flowdeskIsVoiceLocaleSupported(this.appLocale)) {
                return this.unsupportedError;
            }

            return this.localeUnsupportedError;
        },

        init() {
            this.enableWakeWord = Boolean(cfg.enableWakeWord);
            this.wakePhrases = flowdeskVellisWakePhrases(this.assistantName, this.wakeBrand, this.appLocale);
            this.stopPhrases = flowdeskNovaStopPhrases(this.wakeBrand, this.appLocale);

            this.$el.addEventListener('nova-ask-example', (event) => {
                this.askExample(event.detail || '');
            });
            this._onAskExampleWindow = (event) => {
                this.askExample(event.detail || '');
            };
            window.addEventListener('nova-ask-example', this._onAskExampleWindow);

            this._onNovaStop = () => this.applyNovaStopState();
            document.addEventListener('flowdesk-nova-stop', this._onNovaStop);

            this.$nextTick(() => this.initNeuralBackground(cfg.compact));
            this.$watch('neuralEnergy', (value) => {
                this._neuralBg?.setEnergy(value);
            });

            const cleanup = () => {
                this._neuralBg?.destroy();
                this.stopRecognition();
                document.removeEventListener('flowdesk-nova-stop', this._onNovaStop);
                window.removeEventListener('nova-ask-example', this._onAskExampleWindow);
            };
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(cleanup);
            } else {
                this.$el.addEventListener('alpine:destroyed', cleanup, { once: true });
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const voiceLocaleOk = flowdeskIsVoiceLocaleSupported(this.appLocale) && Boolean(this.speechLocale);
            this.voiceSupported = Boolean(SpeechRecognition) && voiceLocaleOk;
            if (!this.voiceSupported) {
                return;
            }

            this.recognition = new SpeechRecognition();
            this.recognition.lang = this.speechLocale;
            this._speechLocaleFallbacks = flowdeskSpeechLocaleFallbacks(this.appLocale);
            this.recognition.interimResults = true;

            this.recognition.onresult = (event) => {
                let interim = '';
                let finalText = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const part = event.results[i][0]?.transcript || '';
                    if (event.results[i].isFinal) {
                        finalText += part;
                    } else {
                        interim += part;
                    }
                }

                const heard = (finalText || interim).trim();
                if (!heard) {
                    return;
                }

                if (this.matchesStopPhrase(heard)) {
                    this.stopNovaResponse();
                    return;
                }

                if (this.speaking) {
                    if (this._listenForStop && this.matchesStopPhrase(heard)) {
                        this.stopNovaResponse();
                    }
                    return;
                }

                if (this.wakeMode && this.state === 'idle') {
                    if (this.matchesWakePhrase(heard)) {
                        this.activateFromWake(finalText || interim);
                    } else if (interim && this.matchesWakePhrase(interim)) {
                        this.activateFromWake(interim);
                    }
                    return;
                }

                if (finalText) {
                    const phrase = flowdeskApplyVoiceDictation(finalText.trim());
                    this.transcript = (this.transcript ? `${this.transcript} ` : '') + phrase;
                    this.draft = this.transcript;
                    if (this._awaitingQuestion && this.state === 'listening') {
                        this._awaitingQuestion = false;
                        this.submitMessage();
                    }
                } else if (interim) {
                    this.draft = (this.transcript ? `${this.transcript} ` : '') + interim.trim();
                }
            };

            this.recognition.onerror = (event) => {
                const code = event?.error || '';
                if (code === 'not-allowed') {
                    this.error = this.permissionError;
                    this.stopRecognition();
                    this.wakeMode = false;
                    this.state = 'idle';
                    return;
                }
                if (code === 'language-not-supported') {
                    const index = this._speechLocaleFallbacks.indexOf(this.speechLocale);
                    const next = this._speechLocaleFallbacks[index + 1];
                    if (next) {
                        this.speechLocale = next;
                        this.recognition.lang = next;
                        this.startRecognition(this.wakeMode || this.state === 'listening');
                        return;
                    }
                }
                if (code === 'aborted') {
                    return;
                }
                if (code === 'no-speech') {
                    if (this.wakeMode) {
                        return;
                    }
                    this.state = 'idle';
                    return;
                }
                this.error = code;
                this.stopRecognition();
                this.state = 'idle';
            };

            this.recognition.onend = () => {
                this.recognitionActive = false;
                if (this._listenForStop && this.speaking) {
                    this.startRecognition(true);
                    return;
                }
                if (this.wakeMode && this.state === 'idle') {
                    this.startRecognition(true);
                    return;
                }
                if (this.state === 'listening') {
                    this.startRecognition(true);
                }
            };

            if (cfg.enableWakeWord && !cfg.skipWakeWord) {
                this.$nextTick(() => this.startWakeMode());
            }
        },

        initNeuralBackground(compact) {
            const canvas = this.$el.querySelector('[data-nova-neural-canvas]');
            if (!canvas) {
                return;
            }
            this._neuralBg?.destroy();
            this._neuralBg = initNovaNeuralBackground(canvas, { compact: Boolean(compact) });
            this._neuralBg.setEnergy(this.neuralEnergy);
        },

        matchesWakePhrase(text) {
            return flowdeskMatchesWakePhrase(text, this.wakePhrases);
        },

        matchesStopPhrase(text) {
            return flowdeskMatchesStopPhrase(text, this.stopPhrases);
        },

        applyNovaStopState() {
            this.speaking = false;
            this.speakingText = '';
            this._awaitingQuestion = false;
            this._listenForStop = false;
            if (this._chatAbort) {
                try {
                    this._chatAbort.abort();
                } catch {
                    // ignore
                }
                this._chatAbort = null;
            }
            if (this.state === 'thinking' || this.state === 'responding') {
                this.state = 'idle';
            }
        },

        stopNovaResponse() {
            flowdeskStopNovaSpeech(this.synthesis);
            this.applyNovaStopState();
            this.transcript = '';
            this.draft = '';
            if (this.enableWakeWord) {
                this.startWakeMode();
            } else {
                this.state = 'idle';
            }
        },

        beginStopListening() {
            if (!this.voiceSupported || !this.recognition) {
                return;
            }
            this._listenForStop = true;
            this.startRecognition(true);
        },

        activateFromWake(raw) {
            this.wakeMode = false;
            this.state = 'listening';
            this.error = '';
            this.transcript = '';
            this.draft = '';
            const cleaned = this.stripWakePrefix(raw);
            if (cleaned) {
                this.transcript = cleaned;
                this.draft = cleaned;
                this.stopRecognition();
                this.submitMessage();
                return;
            }

            this._awaitingQuestion = true;
            this.stopRecognition();
            this.speakWakeReply().then(() => {
                window.setTimeout(() => {
                    if (this._awaitingQuestion && this.state === 'listening') {
                        this.startRecognition(true);
                    }
                }, 350);
            });
        },

        displayMessage(content) {
            return flowdeskIsolateRtlNumbers(content);
        },

        showSpeakNotice(message) {
            const text = flowdeskSanitizeNotifyMessage(
                message,
                flowdeskNotifyLabels().requestFailed || 'Request failed. Please try again.',
            );
            if (!text) {
                return;
            }
            if (typeof window.flowdeskNotify === 'function') {
                window.flowdeskNotify(text, { type: 'warning' });
            }
            this.error = text;
        },

        async speakWakeReply() {
            const text = (this.labels.wakeReply || '').trim();
            if (!text) {
                return;
            }

            this.state = 'responding';
            this.stopRecognition();
            this.speakingText = text;

            try {
                await this.speakText(text, {
                    onStart: () => {
                        this.synthesis?.cancel();
                    },
                });
            } finally {
                this.speakingText = '';
                if (this._awaitingQuestion) {
                    this.state = 'listening';
                } else if (this.wakeMode) {
                    this.state = 'idle';
                } else {
                    this.state = 'idle';
                }
            }
        },

        askExample(text) {
            const question = String(text || '').trim();
            if (!question || this.state === 'thinking') {
                return;
            }

            this.wakeMode = false;
            this._awaitingQuestion = false;
            this.draft = question;
            this.transcript = question;
            this.submitMessage();
        },

        stripWakePrefix(text) {
            let normalized = flowdeskNormalizeVoicePhrase(text);
            for (const phrase of this.wakePhrases) {
                if (normalized.startsWith(phrase)) {
                    normalized = normalized.slice(phrase.length).trim();
                    break;
                }
                if (normalized.includes(phrase)) {
                    normalized = normalized.replace(phrase, '').trim();
                    break;
                }
            }

            return normalized;
        },

        startWakeMode() {
            if (!this.voiceSupported || !this.recognition) {
                return;
            }
            this.wakeMode = true;
            this.state = 'idle';
            this.error = '';
            this.startRecognition(true);
        },

        startRecognition(continuous) {
            if (!this.recognition || this.recognitionActive) {
                return;
            }
            if (this.speaking && !this._listenForStop) {
                return;
            }
            try {
                this.recognition.continuous = continuous;
                this.recognition.start();
                this.recognitionActive = true;
                flowdeskNotifyVoiceBusy();
            } catch {
                this.recognitionActive = false;
            }
        },

        stopRecognition() {
            if (!this.recognition || !this.recognitionActive) {
                return;
            }
            try {
                this.recognition.stop();
            } catch {
                // ignore
            }
            this.recognitionActive = false;
            flowdeskNotifyVoiceIdle();
        },

        toggleVoice() {
            if (!flowdeskIsVoiceLocaleSupported(this.appLocale)) {
                this.error = this.localeUnsupportedError;
                return;
            }
            if (!this.voiceSupported || !this.recognition) {
                this.error = this.unsupportedError;
                return;
            }

            this.error = '';
            this.wakeMode = false;

            if (this.state === 'listening') {
                this.stopRecognition();
                this.state = 'idle';
                return;
            }

            this.transcript = '';
            this.draft = '';
            this.state = 'listening';
            this.stopRecognition();
            this.startRecognition(true);
        },

        async submitMessage() {
            const text = (this.draft || this.transcript || '').trim();
            if (!text || this.state === 'thinking') {
                return;
            }

            if (this.recognition && this.state === 'listening') {
                this.stopRecognition();
            }

            this.wakeMode = false;
            this.error = '';
            const viaVoice = Boolean((this.transcript || '').trim());
            this.state = 'thinking';
            this.messages.push({ role: 'user', content: text });
            this.scrollChat();

            if (this._chatAbort) {
                try {
                    this._chatAbort.abort();
                } catch {
                    // ignore
                }
            }
            this._chatAbort = new AbortController();

            try {
                const res = await fetch(this.chatUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: text,
                        conversation_id: this.conversationId,
                    }),
                    signal: this._chatAbort.signal,
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(flowdeskFetchErrorMessage(res, data, this.labels.requestFailed));
                }

                this.state = 'responding';
                this.lastReply = data.reply || '';
                this.conversationId = data.conversation_id || this.conversationId;
                this.messages.push({ role: 'assistant', content: this.lastReply });
                this.transcript = '';
                this.draft = '';
                this.scrollChat();
                this.state = 'idle';

                if (this.lastReply && viaVoice) {
                    await this.speakText(this.lastReply);
                }

                if (this.enableWakeWord) {
                    this.startWakeMode();
                }
            } catch (e) {
                if (e?.name === 'AbortError') {
                    return;
                }
                this.state = 'idle';
                const message = flowdeskSanitizeNotifyMessage(
                    e?.message,
                    flowdeskNotifyLabels().requestFailed || 'Something went wrong.',
                );
                this.showSpeakNotice(message);
                this.error = message;
                if (this.messages.length > 0 && this.messages[this.messages.length - 1]?.role === 'user') {
                    this.messages.pop();
                }
                this.draft = text;
                if (this.enableWakeWord) {
                    this.startWakeMode();
                }
            } finally {
                this._chatAbort = null;
            }
        },

        speakReply() {
            if (!this.lastReply) {
                return;
            }

            this.speakText(this.lastReply);
        },

        async speakText(text, hooks = {}) {
            const content = String(text || '').trim();
            if (!content) {
                return false;
            }

            try {
                this.beginStopListening();
                this.speakingText = content;
                return await flowdeskSpeakNovaText({
                    text: content,
                    speakUrl: this.speakUrl,
                    speechLocale: this.speechLocale,
                    synthesis: this.synthesis,
                    browserFallbackHint: this.labels.browserFallback || '',
                    onNotice: (message) => this.showSpeakNotice(message),
                    onStart: () => {
                        this.speaking = true;
                        this.transcript = '';
                        this.draft = '';
                        hooks.onStart?.();
                    },
                    onEnd: () => {
                        this.speaking = false;
                        this.speakingText = '';
                        this._listenForStop = false;
                        hooks.onEnd?.();
                    },
                });
            } catch {
                this.speaking = false;
                this.speakingText = '';
                this._listenForStop = false;
                return false;
            }
        },

        loadConversation(id) {
            this.conversationId = id;
            this.messages = [];
            this.transcript = '';
            this.draft = '';
            this.lastReply = '';
        },

        scrollChat() {
            this.$nextTick(() => {
                const el = this.$refs?.chatScroll;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
    });

    Alpine.data('novaAssistant', novaAssistantFactory);
}

if (window.Alpine) {
    registerNovaAssistant(window.Alpine);
}
