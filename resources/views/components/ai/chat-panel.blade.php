@props([
    'messages' => [],
])

<div {{ $attributes->merge(['class' => 'flex min-h-[18rem] flex-col rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5']) }}>
    <div class="border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('nova_chat_title') }}</h3>
    </div>

    <div class="flex-1 space-y-3 overflow-y-auto bg-white p-4" x-ref="chatScroll">
        <template x-if="messages.length === 0 && activities.length === 0">
            <p class="text-sm text-slate-500">{{ __('nova_chat_empty') }}</p>
        </template>
        <template x-for="(msg, idx) in messages" :key="idx">
            <div
                class="rounded-xl px-3 py-2 text-sm"
                :class="msg.role === 'assistant'
                    ? 'border border-sky-200 bg-sky-50 text-slate-900'
                    : 'border border-slate-200 bg-slate-50 text-slate-900 ms-8'"
            >
                <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500" x-text="msg.role === 'assistant' ? assistantName : @js(__('You'))"></p>
                <p class="whitespace-pre-wrap text-slate-900" x-text="displayMessage(msg.content)"></p>
            </div>
        </template>

        <div
            x-show="activities.length > 0"
            x-cloak
            class="rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2"
        >
            <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500" x-text="labels.activityTitle || @js(__('nova_activity_title'))"></p>
            <ul class="space-y-1.5">
                <template x-for="(act, aidx) in activities" :key="act.id || aidx">
                    <li class="flex items-start gap-2 text-xs text-slate-700">
                        <span
                            class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="act.status === 'done' ? 'bg-emerald-500' : 'bg-sky-500 animate-pulse'"
                        ></span>
                        <span x-text="act.message"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div
            x-show="clarificationOptions && clarificationOptions.length"
            x-cloak
            class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3"
        >
            <p class="text-xs font-semibold text-amber-900">{{ __('nova_clarify_prompt') }}</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="(opt, oidx) in (clarificationOptions || [])" :key="opt.id || oidx">
                    <button
                        type="button"
                        class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100"
                        x-on:click="chooseClarification(opt)"
                        x-text="opt.label || opt.id"
                    ></button>
                </template>
            </div>
        </div>

        <div
            x-show="pendingConfirmation"
            x-cloak
            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-3"
        >
            <p class="text-xs font-semibold text-rose-900" x-text="pendingConfirmation?.message || @js(__('nova_confirm_prompt'))"></p>
            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                    x-on:click="confirmPendingAction(true)"
                    x-text="labels.confirm || @js(__('nova_confirm_action'))"
                ></button>
                <button
                    type="button"
                    class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-800 hover:bg-rose-100"
                    x-on:click="confirmPendingAction(false)"
                    x-text="labels.cancel || @js(__('nova_cancel_action'))"
                ></button>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 bg-white p-3">
        <p x-show="error" x-text="error" x-cloak class="mb-2 text-xs text-rose-600"></p>
        <form class="flex gap-2" x-on:submit.prevent="submitMessage()">
            <input
                type="text"
                x-model="draft"
                class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:border-sky-500 focus:ring-sky-500"
                placeholder="{{ __('nova_input_placeholder') }}"
                x-bind:disabled="state === 'thinking'"
            />
            <button
                type="submit"
                class="shrink-0 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
                x-bind:disabled="state === 'thinking' || !(draft || '').trim()"
            >
                <span x-show="state !== 'thinking'">{{ __('Send') }}</span>
                <span x-show="state === 'thinking'" x-cloak>{{ __('nova_state_thinking') }}</span>
            </button>
        </form>
    </div>
</div>
