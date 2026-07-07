@props([])
@php
    $chatBase = rtrim(url('/chat'), '/');
@endphp
<div
    class="pointer-events-none fixed bottom-4 end-4 z-[280] flex flex-col items-end sm:bottom-6 sm:end-6"
    x-data="flowdeskMessenger(@js([
        'bootstrapUrl' => route('chat.widget.bootstrap'),
        'chatBase' => $chatBase,
        'labels' => [
            'client' => __('Client'),
            'provider' => __('Provider'),
        ],
    ]))"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0 scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="pointer-events-auto mb-3 flex max-h-[min(560px,70vh)] w-[min(100vw-2rem,380px)] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/[0.06] dark:border-slate-700/90 dark:bg-slate-900 dark:ring-white/[0.06]"
        style="display: none;"
    >
        <div class="flex shrink-0 items-center justify-between gap-2 bg-gradient-to-r from-[var(--flow-primary)] to-[var(--flow-primary-hover)] px-4 py-3 text-white">
            <div class="min-w-0 flex-1" x-show="view === 'list'" style="display: none;">
                <p class="text-sm font-bold leading-tight">{{ __('Messages') }}</p>
                <p class="text-[11px] text-white/85">{{ __('Chat online') }}</p>
            </div>
            <div class="flex min-w-0 flex-1 items-center gap-2" x-show="view === 'thread'" style="display: none;">
                <button
                    type="button"
                    class="rounded-full p-1 text-white/90 hover:bg-white/15"
                    @click="backToList()"
                    aria-label="{{ __('Back') }}"
                >
                    <i class="fa-solid fa-arrow-left text-sm rtl:rotate-180" aria-hidden="true"></i>
                </button>
                <p class="truncate text-sm font-bold" x-text="threadTitle"></p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <a
                    :href="fullPageUrl"
                    class="rounded-lg p-2 text-white/90 hover:bg-white/15"
                    title="{{ __('Open full page') }}"
                >
                    <i class="fa-solid fa-up-right-from-square text-sm" aria-hidden="true"></i>
                </a>
                <button
                    type="button"
                    class="rounded-lg p-2 text-white/90 hover:bg-white/15"
                    @click="open = false; stopPoll()"
                    aria-label="{{ __('Close') }}"
                >
                    <i class="fa-solid fa-minus text-sm" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col bg-slate-50 dark:bg-slate-950">
            <div x-show="loading && view === 'list'" class="flex flex-1 items-center justify-center p-8 text-sm text-slate-500" style="display: none;">
                <i class="fa-solid fa-spinner fa-spin text-lg" aria-hidden="true"></i>
            </div>

            <div x-show="view === 'list' && !loading" class="max-h-[220px] overflow-y-auto p-2" style="display: none;">
                <p x-show="threads.length === 0" class="px-2 py-6 text-center text-sm text-slate-500 dark:text-slate-400" style="display: none;">
                    {{ __('No conversations yet.') }}
                </p>
                <template x-for="t in threads" :key="t.id">
                    <button
                        type="button"
                        class="mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start transition hover:bg-white dark:hover:bg-slate-800"
                        @click="openThread(t.id, t.label)"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[var(--flow-primary)] to-[var(--flow-primary-hover)] text-sm font-bold text-white" x-text="(t.label || '?').charAt(0).toUpperCase()"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900 dark:text-slate-100" x-text="t.label"></span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400" x-text="typeLabel(t.type)"></span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-slate-400 rtl:rotate-180" aria-hidden="true"></i>
                    </button>
                </template>
            </div>

            <div x-show="view === 'thread'" class="flex min-h-0 flex-1 flex-col" style="display: none;">
                <div x-show="loading" class="flex flex-1 items-center justify-center p-6" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin text-lg text-slate-400" aria-hidden="true"></i>
                </div>
                <div x-show="!loading" class="flex min-h-0 flex-1 flex-col" style="display: none;">
                    <div x-ref="stream" class="flow-messenger-stream min-h-[200px] flex-1 space-y-2 overflow-y-auto p-3">
                        <template x-for="m in messages" :key="m.id">
                            <div class="flex" :class="String(m.user_id) === selfId ? 'justify-end' : 'justify-start'">
                                <div
                                    class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm"
                                    :class="String(m.user_id) === selfId
                                        ? 'rounded-br-md bg-gradient-to-br from-[var(--flow-primary)] to-[var(--flow-primary-hover)] text-white'
                                        : 'rounded-bl-md bg-white text-slate-900 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700'"
                                >
                                    <p class="text-[10px] font-semibold uppercase tracking-wide opacity-80" x-text="m.user_name"></p>
                                    <p class="mt-0.5 whitespace-pre-wrap leading-snug" x-text="m.body"></p>
                                    <p class="mt-1 text-[9px] opacity-70" x-text="formatTime(m.created_at)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="shrink-0 border-t border-slate-200/80 bg-white p-2 dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-2 py-1.5 dark:border-slate-600 dark:bg-slate-800">
                            <textarea
                                x-model="body"
                                rows="1"
                                maxlength="5000"
                                class="max-h-24 min-h-[40px] flex-1 resize-none border-0 bg-transparent py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 dark:text-slate-100"
                                placeholder="{{ __('Type a message…') }}"
                                @keydown.enter.prevent="if (!$event.shiftKey) send()"
                            ></textarea>
                            <button
                                type="button"
                                class="mb-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[var(--flow-primary)] to-[var(--flow-primary-hover)] text-white shadow-md transition hover:opacity-95 disabled:opacity-40"
                                @click="send()"
                                :disabled="sending || !body.trim()"
                                aria-label="{{ __('Send') }}"
                            >
                                <i class="fa-solid fa-paper-plane text-xs rtl:-scale-x-100" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button
        type="button"
        class="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-[var(--flow-primary)] to-[var(--flow-primary-hover)] text-white shadow-lg shadow-slate-900/25 ring-4 ring-white transition hover:scale-105 hover:shadow-xl dark:ring-slate-900"
        @click="toggle()"
        :aria-expanded="open"
        aria-label="{{ __('Messages') }}"
    >
        <i class="fa-solid fa-comment-dots text-2xl" aria-hidden="true"></i>
    </button>
</div>
