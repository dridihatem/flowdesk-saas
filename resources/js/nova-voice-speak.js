import { flowdeskSpeakNovaText } from './flowdesk-nova-speech';

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
