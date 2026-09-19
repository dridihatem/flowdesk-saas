import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const enabled = Boolean(key) && import.meta.env.VITE_REVERB_HOST;

/**
 * Laravel Echo client for Nova activity / TTS realtime.
 * No-ops when Reverb env is not configured (keeps local/tests quiet).
 */
window.Echo = enabled
    ? new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    })
    : null;

export default window.Echo;
