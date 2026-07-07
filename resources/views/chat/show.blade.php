@php
    $lastMessageId = $messages->max('id') ?? 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $thread->resolveDisplayNameFor(auth()->user()) }}</h2>
                @if (auth()->user()->hasRole('client') && ($company ?? $thread->company))
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        <i class="fa-solid fa-building me-1 opacity-70" aria-hidden="true"></i>
                        {{ ($company ?? $thread->company)->name }}
                    </p>
                @endif
            </div>
            <a href="{{ route('chat.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('All threads') }}</a>
        </div>
    </x-slot>

    <div
        class="py-10"
        id="chat-page"
        data-poll-url="{{ route('chat.messages.poll', $thread) }}"
        data-last-id="{{ (int) $lastMessageId }}"
        data-self-id="{{ auth()->id() }}"
    >
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel flex max-h-[min(520px,70vh)] flex-col p-0">
                @if (auth()->user()->hasRole('client') && ($company ?? $thread->company))
                    <div class="border-b border-indigo-200/60 bg-indigo-50/50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-100">
                        {{ __('Chatting with :company', ['company' => ($company ?? $thread->company)->name]) }}
                    </div>
                @endif
                <div class="border-b border-slate-200/80 px-4 py-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('Updates every few seconds (near real time).') }}
                </div>
                <div id="chat-stream" class="flex-1 space-y-3 overflow-y-auto p-4">
                    @foreach ($messages as $m)
                        <div class="rounded-xl px-3 py-2 text-sm {{ (string) $m->user_id === (string) auth()->id() ? 'ms-8 bg-indigo-600 text-white' : 'me-8 bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100' }}" data-msg-id="{{ $m->id }}">
                            <p class="text-[11px] font-semibold uppercase tracking-wide opacity-80">{{ $m->user->name }}</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $m->body }}</p>
                            <p class="mt-1 text-[10px] opacity-70">{{ $m->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <form
                id="chat-send-form"
                method="POST"
                action="{{ route('chat.messages.store', $thread) }}"
                class="flow-panel p-4"
            >
                @csrf
                <x-input-label for="body" :value="__('Message')" />
                <textarea
                    id="body"
                    name="body"
                    rows="3"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    required
                    maxlength="5000"
                    placeholder="{{ __('Type a message…') }}"
                    autocomplete="off"
                >{{ old('body') }}</textarea>
                <p id="chat-send-error" class="mt-2 hidden text-sm text-rose-600 dark:text-rose-400"></p>
                <x-input-error class="mt-2" :messages="$errors->get('body')" />
                <div class="mt-3">
                    <x-primary-button id="chat-send-btn" type="submit">{{ __('Send') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const root = document.getElementById('chat-page');
                if (!root) return;
                const pollUrl = root.dataset.pollUrl;
                let lastId = parseInt(root.dataset.lastId || '0', 10) || 0;
                const selfId = String(root.dataset.selfId || '');
                const stream = document.getElementById('chat-stream');
                const sendForm = document.getElementById('chat-send-form');
                const bodyEl = document.getElementById('body');
                const sendBtn = document.getElementById('chat-send-btn');
                const sendErr = document.getElementById('chat-send-error');
                const storeUrl = @json(route('chat.messages.store', $thread));
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function scrollBottom() {
                    if (stream) stream.scrollTop = stream.scrollHeight;
                }
                function esc(s) {
                    const d = document.createElement('div');
                    d.textContent = s;
                    return d.innerHTML;
                }
                function appendFromPayload(m) {
                    if (!stream || !m) return;
                    const mine = String(m.user_id) === selfId;
                    const wrap = document.createElement('div');
                    wrap.className =
                        'rounded-xl px-3 py-2 text-sm ' +
                        (mine
                            ? 'ms-8 bg-indigo-600 text-white'
                            : 'me-8 bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100');
                    const dt = new Date(m.created_at);
                    const ts =
                        dt.getFullYear() +
                        '-' +
                        String(dt.getMonth() + 1).padStart(2, '0') +
                        '-' +
                        String(dt.getDate()).padStart(2, '0') +
                        ' ' +
                        String(dt.getHours()).padStart(2, '0') +
                        ':' +
                        String(dt.getMinutes()).padStart(2, '0');
                    wrap.innerHTML =
                        '<p class="text-[11px] font-semibold uppercase tracking-wide opacity-80">' +
                        esc(m.user_name) +
                        '</p><p class="mt-1 whitespace-pre-wrap">' +
                        esc(m.body) +
                        '</p><p class="mt-1 text-[10px] opacity-70">' +
                        ts +
                        '</p>';
                    stream.appendChild(wrap);
                    if (m.id > lastId) {
                        lastId = m.id;
                    }
                    root.dataset.lastId = String(lastId);
                    scrollBottom();
                }

                if (sendForm && bodyEl) {
                    sendForm.addEventListener('submit', async function (e) {
                        e.preventDefault();
                        const text = (bodyEl.value || '').trim();
                        if (!text) return;
                        if (sendErr) {
                            sendErr.classList.add('hidden');
                            sendErr.textContent = '';
                        }
                        if (sendBtn) sendBtn.disabled = true;
                        try {
                            const r = await fetch(storeUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ body: text }),
                                credentials: 'same-origin',
                            });
                            const d = await r.json().catch(() => ({}));
                            if (r.ok && d.message) {
                                appendFromPayload(d.message);
                                bodyEl.value = '';
                                bodyEl.focus();
                                return;
                            }
                            let msg = d.message || '';
                            if (!msg && d.errors && d.errors.body) {
                                msg = Array.isArray(d.errors.body) ? d.errors.body[0] : d.errors.body;
                            }
                            if (sendErr) {
                                sendErr.textContent = msg || @json(__('Could not send message.'));
                                sendErr.classList.remove('hidden');
                            }
                        } catch (err) {
                            if (sendErr) {
                                sendErr.textContent = @json(__('Could not send message.'));
                                sendErr.classList.remove('hidden');
                            }
                        } finally {
                            if (sendBtn) sendBtn.disabled = false;
                        }
                    });
                }

                async function poll() {
                    try {
                        const r = await fetch(pollUrl + '?after=' + lastId, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!r.ok) return;
                        const data = await r.json();
                        if (!stream || !data.messages || !data.messages.length) return;
                        for (const m of data.messages) {
                            if (m.id <= lastId) continue;
                            lastId = m.id;
                            const mine = String(m.user_id) === selfId;
                            const wrap = document.createElement('div');
                            wrap.className =
                                'rounded-xl px-3 py-2 text-sm ' +
                                (mine
                                    ? 'ms-8 bg-indigo-600 text-white'
                                    : 'me-8 bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100');
                            const dt = new Date(m.created_at);
                            const ts =
                                dt.getFullYear() +
                                '-' +
                                String(dt.getMonth() + 1).padStart(2, '0') +
                                '-' +
                                String(dt.getDate()).padStart(2, '0') +
                                ' ' +
                                String(dt.getHours()).padStart(2, '0') +
                                ':' +
                                String(dt.getMinutes()).padStart(2, '0');
                            wrap.innerHTML =
                                '<p class="text-[11px] font-semibold uppercase tracking-wide opacity-80">' +
                                esc(m.user_name) +
                                '</p><p class="mt-1 whitespace-pre-wrap">' +
                                esc(m.body) +
                                '</p><p class="mt-1 text-[10px] opacity-70">' +
                                ts +
                                '</p>';
                            stream.appendChild(wrap);
                        }
                        scrollBottom();
                    } catch (e) {}
                }
                scrollBottom();
                setInterval(poll, 2500);
            })();
        </script>
    @endpush
</x-app-layout>
