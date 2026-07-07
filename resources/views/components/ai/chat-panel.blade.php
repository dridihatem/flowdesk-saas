@props([
    'messages' => [],
])

<div {{ $attributes->merge(['class' => 'flex min-h-[18rem] flex-col rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5']) }}>
    <div class="border-b border-slate-200 px-4 py-3">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('nova_chat_title') }}</h3>
    </div>

    <div class="flex-1 space-y-3 overflow-y-auto bg-white p-4" x-ref="chatScroll">
        <template x-if="messages.length === 0">
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
