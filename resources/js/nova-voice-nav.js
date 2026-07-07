import {
    flowdeskIsVoiceLocaleSupported,
    flowdeskMatchesIdentityPhrase,
    flowdeskMatchesStopPhrase,
    flowdeskMatchesWakePhrase,
    flowdeskNormalizeVoicePhrase,
    flowdeskNotifyVoiceIdle,
    flowdeskNovaIdentityPhrases,
    flowdeskNovaStartListeningPhrases,
    flowdeskNovaStopListeningPhrases,
    flowdeskNovaStopPhrases,
    flowdeskSpeechLocale,
    flowdeskSpeechLocaleFallbacks,
    flowdeskVellisWakePhrases,
} from './ai-voice';
import { flowdeskFetchErrorMessage, flowdeskNotifyLabels, flowdeskSanitizeNotifyMessage } from './flowdesk-notify';
import {
    buildBriefingIndex,
    buildCommandIndex,
    buildWorkflowIndex,
    commandQueryVariants,
    isPartialBriefingMatch,
    matchesDirectCommand,
    resolveBestPhraseMatch,
    resolveCommandIntent,
    shouldExecuteCommand,
    stripWakeFromText,
} from './nova-voice-matching';
import { speakNovaVoiceLine } from './nova-voice-speak';
import {
    flowdeskPlayNovaBriefing,
    flowdeskPrefetchNovaSpeech,
    flowdeskSpeakNovaText,
    flowdeskStopNovaSpeech,
    flowdeskUnlockNovaAudio,
} from './flowdesk-nova-speech';

export function registerNovaVoiceNav(Alpine) {
    Alpine.data('novaVoiceNav', (cfg = {}) => ({
        enabled: Boolean(cfg.enabled),
        brand: cfg.brand || 'Nova',
        userName: cfg.userName || '',
        companyName: cfg.companyName || '',
        voiceCreditCost: cfg.voiceCreditCost || 0,
        appLocale: cfg.appLocale || 'en',
        speechLocale: cfg.speechLocale || flowdeskSpeechLocale(cfg.appLocale),
        speakUrl: cfg.speakUrl || null,
        chatUrl: cfg.chatUrl || null,
        chatCreditCost: cfg.chatCreditCost || 0,
        briefingUrl: cfg.briefingUrl || null,
        briefingRedirectUrl: cfg.briefingRedirectUrl || null,
        briefingPhrases: cfg.briefingPhrases || [],
        briefingCreditCost: cfg.briefingCreditCost || 0,
        workflows: cfg.workflows || [],
        workflowUrl: cfg.workflowUrl || null,
        commands: cfg.commands || [],
        labels: cfg.labels || {},
        supported: false,
        active: true,
        commandMode: false,
        commandModeUntil: 0,
        heard: '',
        status: '',
        error: '',
        showWakeTooltip: false,
        wakeTooltipText: '',
        showSpeakingTooltip: false,
        speakingTooltipText: '',
        creditsHint: '',
        recognition: null,
        recognitionActive: false,
        synthesis: typeof window !== 'undefined' ? window.speechSynthesis : null,
        wakePhrases: [],
        stopPhrases: [],
        stopListeningPhrases: [],
        startListeningPhrases: [],
        identityPhrases: [],
        commandIndex: { rows: [], exact: new Map() },
        briefingIndex: { rows: [], exact: new Map() },
        workflowIndex: { rows: [], exact: new Map() },
        _paused: false,
        _listeningPaused: false,
        _speaking: false,
        _briefingLoading: false,
        _wakeAcked: false,
        _listenForStop: false,
        _chatAbort: null,
        _restartTimer: null,
        _audioEl: null,
        _csrf: '',
        _lastHandledKey: '',
        _lastHandledAt: 0,
        _wakePrefetchStarted: false,
        _identityPrefetchStarted: false,
        _chatLoading: false,
        _workflowActive: false,
        _workflowLoading: false,
        conversationId: null,
        _voiceActionsMutedUntil: 0,

        get showStopButton() {
            return this._speaking || this._chatLoading || this._briefingLoading || this._workflowLoading;
        },

        init() {
            if (!this.enabled || !flowdeskIsVoiceLocaleSupported(this.appLocale) || !this.speechLocale) {
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                return;
            }

            this._csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            this.commandIndex = buildCommandIndex(this.commands);
            this.briefingIndex = buildBriefingIndex(this.briefingPhrases);
            this.workflowIndex = buildWorkflowIndex(this.workflows);
            this.wakePhrases = flowdeskVellisWakePhrases(this.brand, this.brand, this.appLocale);
            this.stopPhrases = flowdeskNovaStopPhrases(this.brand, this.appLocale);
            this.stopListeningPhrases = flowdeskNovaStopListeningPhrases(this.brand, this.appLocale);
            this.startListeningPhrases = flowdeskNovaStartListeningPhrases(this.brand, this.appLocale);
            this.identityPhrases = flowdeskNovaIdentityPhrases(this.appLocale);
            this.creditsHint = this.labels.creditsHint || '';
            this.supported = true;
            this.status = this.labels.alwaysOn || this.labels.wake || '';
            this._audioEl = typeof Audio !== 'undefined' ? new Audio() : null;

            this.recognition = new SpeechRecognition();
            this.recognition.lang = this.speechLocale;
            this._speechLocaleFallbacks = flowdeskSpeechLocaleFallbacks(this.appLocale);
            this.recognition.continuous = true;
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

                const isFinal = Boolean(finalText.trim());

                if (this._speaking || this.voiceActionsMuted()) {
                    if (flowdeskMatchesStopPhrase(heard, this.stopPhrases)) {
                        this.stopNovaResponse();
                    }
                    return;
                }

                if (!isFinal && !this._shouldProcessInterim(heard)) {
                    this.heard = heard;
                    return;
                }

                this.heard = heard;
                this.handleSpeech(heard, isFinal);
            };

            this.recognition.onerror = (event) => {
                const code = event?.error || '';
                if (code === 'not-allowed') {
                    this.error = this.labels.unsupported || 'Microphone denied.';
                    this.active = false;
                    return;
                }
                if (code === 'language-not-supported') {
                    const index = this._speechLocaleFallbacks.indexOf(this.speechLocale);
                    const next = this._speechLocaleFallbacks[index + 1];
                    if (next) {
                        this.speechLocale = next;
                        this.recognition.lang = next;
                        this.scheduleRestart(100);
                        return;
                    }
                }
                if (code === 'aborted' || code === 'no-speech') {
                    return;
                }
                if (code === 'network') {
                    this.scheduleRestart(1500);
                    return;
                }
                this.error = code;
                this.scheduleRestart(800);
            };

            this.recognition.onend = () => {
                this.recognitionActive = false;
                if (this.active && !this._paused && (!this._speaking || this._listenForStop)) {
                    this.scheduleRestart(40);
                }
            };

            document.addEventListener('flowdesk-voice-busy', () => {
                this._paused = true;
                this.stopRecognition(false);
            });
            document.addEventListener('flowdesk-voice-idle', () => {
                this._paused = false;
                if (this.active && (!this._speaking || this._listenForStop)) {
                    this.scheduleRestart(60);
                }
            });

            this._onNovaStop = () => this.applyNovaStopState();
            document.addEventListener('flowdesk-nova-stop', this._onNovaStop);

            if (this.synthesis) {
                this.synthesis.getVoices();
            }

            window.flowdeskNovaVoiceNav = this;
            this.prefetchVoiceLines();
            this.ensureListening();

            const cleanup = () => {
                this.active = false;
                if (this._restartTimer) {
                    window.clearTimeout(this._restartTimer);
                }
                this.synthesis?.cancel();
                this.stopRecognition(true);
                document.removeEventListener('flowdesk-nova-stop', this._onNovaStop);
                if (window.flowdeskNovaVoiceNav === this) {
                    window.flowdeskNovaVoiceNav = null;
                }
            };
            if (typeof this.$cleanup === 'function') {
                this.$cleanup(cleanup);
            } else {
                this.$el.addEventListener('alpine:destroyed', cleanup, { once: true });
            }
        },

        _shouldProcessInterim(heard) {
            if (this._workflowActive || this._listeningPaused) {
                return true;
            }
            if (this.commandMode && Date.now() < this.commandModeUntil) {
                return true;
            }
            if (flowdeskMatchesWakePhrase(heard, this.wakePhrases)) {
                return true;
            }
            if (flowdeskMatchesStopPhrase(heard, this.stopListeningPhrases)) {
                return true;
            }
            if (flowdeskMatchesStopPhrase(heard, this.startListeningPhrases)) {
                return true;
            }

            const normalized = flowdeskNormalizeVoicePhrase(heard);
            return matchesDirectCommand(normalized, this.commandIndex);
        },

        unlockAudio() {
            flowdeskUnlockNovaAudio();
            if (this._audioEl) {
                this._audioEl.src = '';
                this._audioEl.play().catch(() => {});
            }
            if (this.synthesis && this.synthesis.getVoices().length === 0) {
                this.synthesis.getVoices();
            }
            this.error = '';
            if (this._listeningPaused) {
                this.resumeListening();
            } else {
                this.ensureListening();
            }
            this.prefetchVoiceLines();
        },

        toggleMic() {
            flowdeskUnlockNovaAudio();
            if (this._listeningPaused) {
                this.resumeListening(true);
                return;
            }
            this.unlockAudio();
        },

        pauseListening() {
            if (this._listeningPaused) {
                return;
            }

            this.stopNovaResponse();
            this._listeningPaused = true;
            this.commandMode = false;
            this.commandModeUntil = 0;
            this._wakeAcked = false;
            this.heard = '';
            this.showWakeTooltip = false;
            this.status = this.labels.notListening || '';
            this.speakNotListening();
        },

        resumeListening(fromMic = false) {
            if (!this._listeningPaused) {
                return;
            }

            this._listeningPaused = false;
            this.error = '';
            this.heard = '';
            this.status = this.labels.alwaysOn || this.labels.wake || '';
            this.ensureListening();

            if (fromMic && (this.labels.listeningAgain || '').trim()) {
                this.speakListeningAgain();
            }
        },

        prefetchVoiceLines() {
            this.prefetchSpeechLine(this.labels.wakeReply, '_wakePrefetchStarted');
            this.prefetchSpeechLine(this.labels.identityReply, '_identityPrefetchStarted');
        },

        prefetchSpeechLine(text, flagName) {
            const value = String(text || '').trim();
            if (!value || !this.speakUrl || this[flagName]) {
                return;
            }
            this[flagName] = true;
            flowdeskPrefetchNovaSpeech({ text: value, speakUrl: this.speakUrl }).catch(() => {});
        },

        voiceActionsMuted() {
            return Date.now() < this._voiceActionsMutedUntil;
        },

        muteVoiceActions(durationMs = 3000) {
            this._voiceActionsMutedUntil = Math.max(
                this._voiceActionsMutedUntil,
                Date.now() + Math.max(0, durationMs),
            );
        },

        handleSpeech(raw, isFinal) {
            if (flowdeskMatchesStopPhrase(raw, this.stopListeningPhrases)) {
                this.pauseListening();
                return;
            }

            if (this._listeningPaused) {
                const fromStartPhrase = flowdeskMatchesStopPhrase(raw, this.startListeningPhrases);
                const fromWake = flowdeskMatchesWakePhrase(raw, this.wakePhrases);
                if (!fromStartPhrase && !fromWake) {
                    return;
                }
                this._listeningPaused = false;
                this.error = '';
                this.status = this.labels.alwaysOn || this.labels.wake || '';
                if (fromStartPhrase && !fromWake) {
                    if ((this.labels.listeningAgain || '').trim()) {
                        this.speakListeningAgain();
                    } else {
                        this.ensureListening();
                    }
                    return;
                }
            }

            if (this._workflowActive) {
                if (isFinal) {
                    this.advanceWorkflow(raw);
                } else {
                    this.status = this.labels.workflowListening || this.labels.chatListening || '';
                }
                return;
            }

            if (flowdeskMatchesStopPhrase(raw, this.stopPhrases)) {
                this.stopNovaResponse();
                return;
            }

            if (isFinal && this.matchesIdentityQuestion(raw)) {
                this.speakIdentityReply();
                return;
            }

            if (this._speaking || this._chatLoading || this._briefingLoading || this._workflowLoading || this.voiceActionsMuted()) {
                return;
            }

            if (Date.now() > this.commandModeUntil) {
                this.commandMode = false;
                this._wakeAcked = false;
            }

            const normalized = flowdeskNormalizeVoicePhrase(raw);
            if (!normalized) {
                return;
            }

            const hasWake = flowdeskMatchesWakePhrase(raw, this.wakePhrases);
            const inCommandWindow = this.commandMode && Date.now() < this.commandModeUntil;

            if (hasWake && !inCommandWindow) {
                this.commandMode = true;
                this.commandModeUntil = Date.now() + 20000;
                this.status = this.labels.chatListening || this.labels.listening || '';

                const afterWake = stripWakeFromText(raw, this.wakePhrases);
                if (afterWake && this.matchesIdentityQuestion(afterWake) && isFinal) {
                    this.speakIdentityReply();
                    return;
                }
                if (afterWake && this.shouldExecuteCommand(afterWake, isFinal)) {
                    this.tryHandleCommand(afterWake, isFinal);
                    return;
                }

                if (!afterWake && !this._wakeAcked && isFinal) {
                    this._wakeAcked = true;
                    this.speakWakeReply();
                }
                return;
            }

            if (inCommandWindow || matchesDirectCommand(normalized, this.commandIndex) || this.matchesBriefing(normalized)) {
                const commandText = stripWakeFromText(raw, this.wakePhrases);
                if (this.shouldExecuteCommand(commandText, isFinal)) {
                    this.tryHandleCommand(commandText, isFinal);
                } else {
                    this.status = this.labels.chatListening || this.labels.listening || '';
                }
            }
        },

        shouldExecuteCommand(text, isFinal) {
            return shouldExecuteCommand(text, isFinal, this.briefingIndex, this.commandIndex);
        },

        matchesBriefing(normalized) {
            return Boolean(resolveBestPhraseMatch(normalized, this.wakePhrases, this.briefingIndex, { fromNormalized: true }));
        },

        matchesIdentityQuestion(text) {
            return flowdeskMatchesIdentityPhrase(text, this.identityPhrases);
        },

        tryHandleCommand(text, isFinal = true) {
            const intent = resolveCommandIntent(text, this.wakePhrases, {
                briefing: this.briefingIndex,
                workflow: this.workflowIndex,
                command: this.commandIndex,
            }, { isFinal });

            const { variants, briefing, workflow, command } = intent;
            if (variants.length === 0) {
                return;
            }

            const handleKey = variants.join('|');
            if (handleKey === this._lastHandledKey && Date.now() - this._lastHandledAt < 2000) {
                return;
            }

            if (briefing) {
                this._lastHandledKey = handleKey;
                this._lastHandledAt = Date.now();
                this.playBriefing();
                return;
            }

            if (!isFinal) {
                for (const query of variants) {
                    if (isPartialBriefingMatch(query, this.briefingIndex)) {
                        return;
                    }
                }
            }

            if (workflow) {
                if (!isFinal && workflow.score < 10000) {
                    return;
                }

                this._lastHandledKey = handleKey;
                this._lastHandledAt = Date.now();
                this.startWorkflow(workflow.cmd.id);
                return;
            }

            if (command) {
                if (!isFinal && command.score < 10000) {
                    return;
                }

                this._lastHandledKey = handleKey;
                this._lastHandledAt = Date.now();
                this.navigateToMatch(command);
                return;
            }

            if (isFinal && Date.now() < this.commandModeUntil) {
                const question = stripWakeFromText(text, this.wakePhrases).trim();
                if (question.length >= 3 && this.matchesIdentityQuestion(question)) {
                    this.speakIdentityReply();
                    return;
                }
                if (question.length >= 3 && this.chatUrl) {
                    this.askNovaChat(question);
                    return;
                }

                this.error = this.labels.unknown || '';
                window.setTimeout(() => {
                    this.error = '';
                }, 3500);
            }
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
            if (this._speakNoticeTimer) {
                window.clearTimeout(this._speakNoticeTimer);
            }
            this._speakNoticeTimer = window.setTimeout(() => {
                this.error = '';
            }, 7000);
        },

        applyNovaStopState() {
            this._speaking = false;
            this._chatLoading = false;
            this._briefingLoading = false;
            this._listenForStop = false;
            this.commandMode = false;
            this._wakeAcked = false;
            this.showWakeTooltip = false;
            if (this._chatAbort) {
                try {
                    this._chatAbort.abort();
                } catch {
                    // ignore
                }
                this._chatAbort = null;
            }
            this.status = this._listeningPaused
                ? (this.labels.notListening || '')
                : (this.labels.alwaysOn || this.labels.wake || '');
        },

        stopNovaResponse() {
            flowdeskStopNovaSpeech(this.synthesis);
            if (this._workflowLoading) {
                this._workflowLoading = false;
            }
            if (this._workflowActive && this.workflowUrl) {
                this.postWorkflow({ action: 'cancel' }).catch(() => {});
                this._workflowActive = false;
                this.commandMode = false;
            }
            this.applyNovaStopState();
            if (this._listeningPaused) {
                this.scheduleRestart(400);
            } else {
                this.ensureListening();
            }
        },

        beginStopListening() {
            if (!this.active || !this.recognition) {
                return;
            }
            this._listenForStop = true;
            this.startRecognition();
        },

        async askNovaChat(question) {
            if (this._chatLoading || !this.chatUrl) {
                return;
            }

            this._chatLoading = true;
            this.commandMode = false;
            this._wakeAcked = false;
            this.showWakeTooltip = false;
            this.error = '';
            this.status = this.labels.thinking || '';

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
                        'X-CSRF-TOKEN': this._csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: question,
                        conversation_id: this.conversationId,
                    }),
                    signal: this._chatAbort.signal,
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(flowdeskFetchErrorMessage(res, data, this.labels.requestFailed));
                }

                this.conversationId = data.conversation_id || this.conversationId;
                const reply = (data.reply || '').trim();
                if (!reply) {
                    throw new Error(this.labels.unknown || 'No reply');
                }

                this.status = this.labels.answering || '';
                this.showSpokenTooltip(reply);
                this.muteVoiceActions(8000);
                await flowdeskSpeakNovaText({
                    text: reply,
                    speakUrl: this.speakUrl,
                    speechLocale: this.speechLocale,
                    synthesis: this.synthesis,
                    browserFallbackHint: this.labels.browserFallback || '',
                    onNotice: (message) => this.showSpeakNotice(message),
                    onStart: () => {
                        this._speaking = true;
                        this.heard = '';
                        this.beginStopListening();
                    },
                    onEnd: () => {
                        this._speaking = false;
                        this._listenForStop = false;
                        this.muteVoiceActions(3000);
                        this.clearSpokenTooltip();
                    },
                });
            } catch (e) {
                if (e?.name === 'AbortError') {
                    return;
                }
                const message = flowdeskSanitizeNotifyMessage(
                    e?.message,
                    flowdeskNotifyLabels().requestFailed || this.labels.unknown || '',
                );
                this.showSpeakNotice(message);
                window.setTimeout(() => {
                    this.error = '';
                }, 4500);
            } finally {
                this._chatLoading = false;
                this._speaking = false;
                this._listenForStop = false;
                this._chatAbort = null;
                this.clearSpokenTooltip();
                this.status = this.labels.alwaysOn || this.labels.wake || '';
                this.ensureListening();
            }
        },

        async playBriefing() {
            if (this._briefingLoading || this._speaking || !this.briefingUrl) {
                return;
            }

            this._briefingLoading = true;
            this.commandMode = false;
            this._wakeAcked = false;
            this.showWakeTooltip = false;
            this.error = '';
            this.status = this.labels.briefing || '';

            flowdeskUnlockNovaAudio();
            this.muteVoiceActions(120000);

            const ok = await flowdeskPlayNovaBriefing({
                briefingUrl: this.briefingUrl,
                speechLocale: this.speechLocale,
                synthesis: this.synthesis,
                browserFallbackHint: this.labels.browserFallback || '',
                onNotice: (message) => this.showSpeakNotice(message),
                onStart: () => {
                    this._speaking = true;
                    this._listenForStop = true;
                    this.beginStopListening();
                },
                onEnd: () => {
                    this._speaking = false;
                    this._briefingLoading = false;
                    this._listenForStop = false;
                    this.muteVoiceActions(4000);
                    this.status = this.labels.alwaysOn || this.labels.wake || '';
                    this.ensureListening();
                },
            });

            this._speaking = false;
            this._briefingLoading = false;
            this.muteVoiceActions(4000);

            if (!ok) {
                this.error = this.labels.briefingError || '';
                window.setTimeout(() => {
                    this.error = '';
                }, 4500);
            }

            this.ensureListening();
        },

        showSpokenTooltip(text) {
            const value = String(text || '').trim();
            if (!value) {
                return;
            }

            this.speakingTooltipText = value;
            this.showSpeakingTooltip = true;
            this.showWakeTooltip = false;
            this.heard = '';
            if (this._speakingTooltipTimer) {
                window.clearTimeout(this._speakingTooltipTimer);
            }
            this._speakingTooltipTimer = window.setTimeout(() => {
                if (!this._speaking) {
                    this.clearSpokenTooltip();
                }
            }, 120000);
        },

        clearSpokenTooltip() {
            this.showSpeakingTooltip = false;
            this.speakingTooltipText = '';
            if (this._speakingTooltipTimer) {
                window.clearTimeout(this._speakingTooltipTimer);
                this._speakingTooltipTimer = null;
            }
        },

        speakWakeReply() {
            return speakNovaVoiceLine(this, {
                text: this.labels.wakeReply,
                status: this.labels.chatListening || this.labels.listening || '',
                muteMs: 6000,
                afterMuteMs: 2500,
            });
        },

        speakNotListening() {
            return speakNovaVoiceLine(this, {
                text: this.labels.notListening,
                muteMs: 8000,
                afterMuteMs: 3000,
                onAfterSpeak: () => {
                    this.status = this.labels.notListening || '';
                    if (this._listeningPaused) {
                        this.scheduleRestart(400);
                    }
                },
                ensureListeningDelayMs: -1,
            });
        },

        speakListeningAgain() {
            return speakNovaVoiceLine(this, {
                text: this.labels.listeningAgain,
                muteMs: 5000,
                afterMuteMs: 2000,
                onAfterSpeak: () => {
                    this.status = this.labels.alwaysOn || this.labels.wake || '';
                    this.ensureListening();
                },
                ensureListeningDelayMs: -1,
            });
        },

        speakIdentityReply() {
            this.commandMode = true;
            this.commandModeUntil = Date.now() + 20000;
            this._wakeAcked = true;
            this.showWakeTooltip = false;

            return speakNovaVoiceLine(this, {
                text: this.labels.identityReply,
                status: this.labels.answering || this.labels.chatListening || '',
                muteMs: 20000,
                afterMuteMs: 2500,
                onAfterSpeak: () => {
                    this.status = this.labels.chatListening || this.labels.alwaysOn || '';
                },
            });
        },

        async postWorkflow(payload) {
            const response = await fetch(this.workflowUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this._csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || this.labels.workflowError || this.labels.unknown || 'Request failed');
            }

            return data;
        },

        async startWorkflow(workflowId) {
            if (!this.workflowUrl || this._workflowLoading || !workflowId) {
                return;
            }

            this._workflowLoading = true;
            this.commandMode = true;
            this.commandModeUntil = Date.now() + 120000;
            this._workflowActive = true;
            this.error = '';
            this.heard = '';
            this.showWakeTooltip = false;

            try {
                const result = await this.postWorkflow({ action: 'start', workflow: workflowId });
                await this.speakWorkflowReply(result.reply || '');
                this.status = result.active
                    ? (this.labels.workflowListening || this.labels.chatListening || '')
                    : (this.labels.alwaysOn || this.labels.wake || '');
                if (result.done) {
                    this._workflowActive = false;
                    this.commandMode = false;
                }
            } catch (error) {
                this._workflowActive = false;
                this.commandMode = false;
                this.error = error?.message || this.labels.workflowError || '';
                window.setTimeout(() => {
                    this.error = '';
                }, 4000);
            } finally {
                this._workflowLoading = false;
                this.ensureListening();
            }
        },

        async advanceWorkflow(input) {
            if (!this.workflowUrl || this._workflowLoading) {
                return;
            }

            this._workflowLoading = true;
            this.error = '';

            try {
                const result = await this.postWorkflow({ action: 'advance', input });
                await this.speakWorkflowReply(result.reply || '');
                if (result.done) {
                    this._workflowActive = false;
                    this.commandMode = false;
                    this.status = this.labels.alwaysOn || this.labels.wake || '';
                    if (result.redirect_url) {
                        window.setTimeout(() => {
                            window.location.href = result.redirect_url;
                        }, 600);
                    }
                } else {
                    this.status = this.labels.workflowListening || this.labels.chatListening || '';
                }
            } catch (error) {
                this.error = error?.message || this.labels.workflowError || '';
                window.setTimeout(() => {
                    this.error = '';
                }, 4000);
            } finally {
                this._workflowLoading = false;
                this.heard = '';
                this.ensureListening();
            }
        },

        speakWorkflowReply(text) {
            return speakNovaVoiceLine(this, {
                text,
                muteMs: 8000,
                afterMuteMs: 2000,
                ensureListeningDelayMs: -1,
            });
        },

        navigateToMatch(match) {
            this.error = '';
            this.commandMode = false;
            this._wakeAcked = false;
            this.showWakeTooltip = false;

            if (match.cmd.action === 'logout') {
                this.status = this.labels.loggingOut || '';
                window.setTimeout(() => this.performLogout(match.cmd.url), 80);
                return;
            }

            this.status = `${this.labels.navigating || ''} ${match.cmd.label}`.trim();
            window.setTimeout(() => {
                window.location.href = match.cmd.url;
            }, 80);
        },

        performLogout(url) {
            const action = String(url || '').trim();
            if (!action) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            if (this._csrf) {
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = this._csrf;
                form.appendChild(token);
            }

            document.body.appendChild(form);
            form.submit();
        },

        ensureListening() {
            if (!this.active || this._paused || this._listeningPaused || (this._speaking && !this._listenForStop)) {
                return;
            }
            this.scheduleRestart(0);
        },

        scheduleRestart(delayMs) {
            if (this._restartTimer) {
                window.clearTimeout(this._restartTimer);
            }
            this._restartTimer = window.setTimeout(() => {
                this._restartTimer = null;
                this.startRecognition();
            }, delayMs);
        },

        startRecognition() {
            if (!this.recognition || this.recognitionActive || this._paused || !this.active) {
                return;
            }
            if (this._workflowLoading) {
                return;
            }
            if (this._speaking && !this._listenForStop) {
                return;
            }
            try {
                this.recognition.start();
                this.recognitionActive = true;
                this.status = this._listeningPaused
                    ? (this.labels.notListening || '')
                    : (this.labels.alwaysOn || this.labels.wake || '');
                this.error = '';
            } catch {
                this.recognitionActive = false;
                this.scheduleRestart(150);
            }
        },

        stopRecognition(notifyIdle) {
            if (!this.recognition || !this.recognitionActive) {
                return;
            }
            try {
                this.recognition.stop();
            } catch {
                // ignore
            }
            this.recognitionActive = false;
            if (notifyIdle) {
                flowdeskNotifyVoiceIdle();
            }
        },
    }));
}
