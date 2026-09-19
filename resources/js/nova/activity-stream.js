/**
 * Subscribe to Nova agent activity on a run channel (Reverb / Echo).
 * Safe no-op when Echo is not configured.
 */
export function subscribeNovaRunActivity(runId, onActivity) {
    if (! window.Echo || ! runId) {
        return () => {};
    }

    const channel = window.Echo.private(`nova.run.${runId}`);
    channel.listen('.NovaActivityCreated', (payload) => {
        if (typeof onActivity === 'function') {
            onActivity(payload);
        }
    });

    return () => {
        window.Echo.leave(`nova.run.${runId}`);
    };
}

export function subscribeNovaCompanyActivity(companyId, onActivity) {
    if (! window.Echo || ! companyId) {
        return () => {};
    }

    const channel = window.Echo.private(`nova.company.${companyId}`);
    channel.listen('.NovaActivityCreated', (payload) => {
        if (typeof onActivity === 'function') {
            onActivity(payload);
        }
    });
    channel.listen('.NovaTtsStatusUpdated', (payload) => {
        window.dispatchEvent(new CustomEvent('nova:tts-status', { detail: payload }));
    });

    return () => {
        window.Echo.leave(`nova.company.${companyId}`);
    };
}
