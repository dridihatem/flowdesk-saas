import { flowdeskSpeakNovaText } from './flowdesk-nova-speech';

const WAKE_GREETED_PREFIX = 'flowdesk_nova_wake_greeted:';

export function flowdeskNovaWakeReplyStorageKey(userId = '') {
    const id = String(userId || '').trim();

    return `${WAKE_GREETED_PREFIX}${id || 'guest'}`;
}

export function flowdeskNovaHasWakeGreeted(storageKey) {
    if (!storageKey || typeof window === 'undefined') {
        return false;
    }

    try {
        return window.sessionStorage.getItem(storageKey) === '1';
    } catch {
        return false;
    }
}

export function flowdeskNovaMarkWakeGreeted(storageKey) {
    if (!storageKey || typeof window === 'undefined') {
        return;
    }

    try {
        window.sessionStorage.setItem(storageKey, '1');
    } catch {
        // ignore quota / privacy mode
    }
}

/**
 * First wake in the browser session: hello line. Later wakes: listening line.
 */
export function flowdeskNovaResolveWakeReply({ storageKey, hello = '', listening = '', legacy = '' } = {}) {
    const helloText = String(hello || legacy || '').trim();
    const listeningText = String(listening || helloText).trim();
    const firstTime = !flowdeskNovaHasWakeGreeted(storageKey);

    return {
        text: firstTime ? helloText : listeningText,
        firstTime,
    };
}

/**
 * Shared TTS path for Nova top-bar voice replies.
 */
export async function speakNovaVoiceLine(ctx, {
    text,
    status = '',
    muteMs = 6000,
    afterMuteMs = 2500,
    onBeforeSpeak = null,
    onAfterSpeak = null,
    ensureListeningDelayMs = 400,
} = {}) {
    const value = String(text || '').trim();
    if (!value || ctx._speaking) {
        return false;
    }

    if (ctx._restartTimer) {
        window.clearTimeout(ctx._restartTimer);
        ctx._restartTimer = null;
    }

    ctx._speaking = true;
    ctx.heard = '';
    ctx.muteVoiceActions(muteMs);
    ctx.showSpokenTooltip(value);
    ctx.beginStopListening();
    if (status) {
        ctx.status = status;
    }

    if (typeof onBeforeSpeak === 'function') {
        onBeforeSpeak();
    }

    try {
        await flowdeskSpeakNovaText({
            text: value,
            speakUrl: ctx.speakUrl,
            speechLocale: ctx.speechLocale,
            synthesis: ctx.synthesis,
            browserFallbackHint: ctx.labels.browserFallback || '',
            onNotice: (message) => ctx.showSpeakNotice(message),
            onStart: () => {
                ctx.synthesis?.cancel();
            },
            onEnd: () => {},
        });
    } finally {
        ctx._speaking = false;
        ctx._listenForStop = false;
        ctx.muteVoiceActions(afterMuteMs);
        ctx.clearSpokenTooltip();

        if (typeof onAfterSpeak === 'function') {
            onAfterSpeak();
        } else if (ensureListeningDelayMs >= 0) {
            window.setTimeout(() => {
                if (!ctx._speaking && !ctx._chatLoading && !ctx._briefingLoading) {
                    ctx.ensureListening();
                }
            }, ensureListeningDelayMs);
        }
    }

    return true;
}
