/**
 * Lightweight toast notifications (global).
 */

export function flowdeskSanitizeNotifyMessage(text, fallback = '') {
    const raw = String(text || '').replace(/\s+/g, ' ').trim();
    if (!raw) {
        return fallback;
    }

    if (/^<!doctype/i.test(raw) || /^<html[\s>]/i.test(raw)) {
        return fallback;
    }

    if (raw.startsWith('<') && /<\/[a-z][\w:-]*>/i.test(raw)) {
        return fallback;
    }

    if (raw.length > 300) {
        return `${raw.slice(0, 297)}…`;
    }

    return raw;
}

function statusFallbackMessage(res, labels, fallback) {
    if (res.status === 403) {
        return labels.creditsLimit || fallback;
    }

    if (res.status === 419) {
        return labels.sessionExpired || fallback;
    }

    if (res.status === 503) {
        return labels.serviceUnavailable || fallback;
    }

    return fallback;
}

export function flowdeskFetchErrorMessage(res, data = {}, defaultMessage = '') {
    const labels = flowdeskNotifyLabels();
    const fallback = defaultMessage || labels.requestFailed || 'Request failed. Please try again.';
    const contentType = res.headers.get('Content-Type') || '';

    if (contentType.includes('application/json')) {
        return flowdeskSanitizeNotifyMessage(data?.message, fallback) || statusFallbackMessage(res, labels, fallback);
    }

    return statusFallbackMessage(res, labels, fallback);
}

export async function flowdeskReadFetchErrorMessage(res, defaultMessage = '') {
    const labels = flowdeskNotifyLabels();
    const fallback = defaultMessage || labels.requestFailed || 'Request failed. Please try again.';
    const contentType = res.headers.get('Content-Type') || '';

    if (contentType.includes('application/json')) {
        const data = await res.json().catch(() => ({}));

        return flowdeskFetchErrorMessage(res, data, fallback);
    }

    await res.text().catch(() => '');

    return statusFallbackMessage(res, labels, fallback);
}

function ensureNotifyRoot() {
    let root = document.getElementById('flowdesk-notify-root');
    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.id = 'flowdesk-notify-root';
    root.className = 'flowdesk-notify-root';
    root.setAttribute('aria-live', 'polite');
    root.setAttribute('aria-relevant', 'additions');
    document.body.appendChild(root);

    return root;
}

export function flowdeskNotify(message, { type = 'info', duration = 7000 } = {}) {
    const text = flowdeskSanitizeNotifyMessage(message, '');
    if (!text || typeof document === 'undefined') {
        return;
    }

    const root = ensureNotifyRoot();
    const toast = document.createElement('div');
    toast.className = `flowdesk-notify flowdesk-notify--${type}`;
    toast.setAttribute('role', 'status');
    toast.textContent = text;
    root.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    window.setTimeout(() => {
        toast.classList.remove('is-visible');
        window.setTimeout(() => toast.remove(), 320);
    }, Math.max(2000, duration));
}

export function flowdeskNotifyLabels() {
    return window.flowdeskNotifyLabels || {};
}
