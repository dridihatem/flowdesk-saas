/**
 * Flowdesk sitewide marketing tracker: records page URL, path, referrer, and title for the Marketing hub.
 * Install once per page (typically before </body>) on your public website, alongside the lead-form widget if you use it.
 */
(function () {
    const script = document.currentScript;
    if (!script) {
        return;
    }
    const baseUrl = (script.dataset.baseUrl || '').replace(/\/$/, '');
    const token = (script.dataset.token || '').trim();
    if (!baseUrl || !token || token.indexOf('fd_live_') !== 0 || token.includes('YOUR') || token.length < 28) {
        return;
    }

    const payload = {
        page_url: window.location.href,
        path: window.location.pathname + window.location.search,
        referrer: document.referrer || null,
        title: document.title || null,
    };

    const url = `${baseUrl}/api/v1/embed/track`;
    const body = JSON.stringify(payload);
    const headers = {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
    };

    function sleep(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    async function postOnce() {
        const res = await fetch(url, {
            method: 'POST',
            headers,
            body,
            credentials: 'omit',
            mode: 'cors',
            keepalive: true,
        });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
    }

    async function runWithRetries() {
        const delays = [0, 900, 2400];
        for (let i = 0; i < delays.length; i++) {
            if (delays[i] > 0) {
                await sleep(delays[i]);
            }
            try {
                await postOnce();
                return;
            } catch (_) {
                /* try next attempt */
            }
        }
    }

    try {
        void runWithRetries();
    } catch (_) {
        /* ignore */
    }
})();
