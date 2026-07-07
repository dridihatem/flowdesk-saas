@props([
    'assistantName',
    'assistantUrl' => null,
    'compact' => false,
])

<div
    {{ $attributes->merge([
        'class' => 'nova-assistant-card relative overflow-hidden rounded-3xl border shadow-2xl ring-1 transition-[border-color,box-shadow] duration-300'
            . ($compact ? ' p-4' : ' p-5 sm:p-6'),
    ]) }}
    :class="{
        'nova-assistant-card--listening': cardState === 'listening',
        'nova-assistant-card--speaking': cardState === 'speaking',
        'nova-assistant-card--thinking': cardState === 'thinking',
        'nova-assistant-card--wake': wakeMode && cardState === 'idle',
    }"
>
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <canvas data-nova-neural-canvas class="h-full w-full"></canvas>
        <div class="nova-assistant-card__overlay absolute inset-0"></div>
    </div>

    <div class="relative z-10">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 text-start">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-sky-300/90">{{ $assistantName }}</p>
                    @if ($assistantUrl)
                        <a href="{{ $assistantUrl }}" class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-sky-400 transition hover:text-sky-300">
                            {{ __('nova_open_full') }}
                        </a>
                    @endif
                </div>
                <p
                    @class(['mt-1 font-semibold text-sky-50', $compact ? 'text-xs line-clamp-3' : 'text-sm'])
                    x-text="stateLabel"
                ></p>
            </div>

            <div
                class="nova-assistant-card__status shrink-0"
                :class="{
                    'nova-assistant-card__status--active': voiceActive,
                    'nova-assistant-card__status--speaking': cardState === 'speaking',
                    'nova-assistant-card__status--listening': cardState === 'listening',
                }"
                :aria-label="stateLabel"
            >
                <span class="nova-assistant-card__status-dot" aria-hidden="true"></span>
                <span class="nova-assistant-card__status-label line-clamp-2 max-w-[8rem] text-end" x-text="statusBadgeLabel"></span>
            </div>
        </div>

        <div @class(['flex flex-col items-center', $compact ? 'mt-3' : 'mt-5 sm:mt-6'])>
            <div @class(['relative flex items-center justify-center', $compact ? 'h-24 w-24' : 'h-32 w-32 sm:h-36 sm:w-36'])>
                <span
                    class="nova-assistant-card__ring nova-assistant-card__ring--outer absolute inset-0 rounded-full"
                    :class="{ 'is-active': voiceActive }"
                    aria-hidden="true"
                ></span>
                <span
                    class="nova-assistant-card__ring nova-assistant-card__ring--inner absolute inset-3 rounded-full"
                    :class="{
                        'is-active': cardState === 'thinking' || cardState === 'speaking',
                        'is-speaking': cardState === 'speaking',
                    }"
                    aria-hidden="true"
                ></span>

                <button
                    type="button"
                    @class([
                        'nova-assistant-card__mic relative flex items-center justify-center rounded-full text-white shadow-lg transition duration-200 hover:scale-[1.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 disabled:opacity-60',
                        $compact ? 'h-14 w-14' : 'h-20 w-20 sm:h-24 sm:w-24',
                    ])
                    :class="{
                        'nova-assistant-card__mic--listening': cardState === 'listening',
                        'nova-assistant-card__mic--speaking': cardState === 'speaking',
                    }"
                    x-on:click="toggleVoice()"
                    x-bind:disabled="!voiceSupported || state === 'thinking' || speaking"
                    :aria-label="cardState === 'listening' ? @js(__('nova_stop_listening')) : @js(__('nova_start_listening'))"
                >
                    <i
                        @class(['fa-solid', $compact ? 'text-base' : 'text-xl sm:text-2xl'])
                        :class="cardState === 'listening' ? 'fa-stop' : (cardState === 'speaking' ? 'fa-volume-high' : 'fa-microphone')"
                        aria-hidden="true"
                    ></i>
                </button>
            </div>

            <div
                class="nova-assistant-card__waves mt-4 flex h-8 items-end justify-center gap-1"
                :class="{ 'is-active': voiceActive }"
                aria-hidden="true"
            >
                @foreach (range(1, 7) as $bar)
                    <span class="nova-assistant-card__wave" style="--nova-wave-index: {{ $bar }}"></span>
                @endforeach
            </div>
        </div>

        <p
            x-show="!voiceSupported && !isCompact"
            x-cloak
            class="mt-3 text-center text-xs text-amber-300/90"
            x-text="voiceUnavailableMessage"
        ></p>

        <p
            x-show="isCompact && speaking && speakingText"
            x-text="speakingText"
            x-cloak
            class="mx-auto mt-3 max-w-md text-center text-xs leading-relaxed text-slate-300 line-clamp-4"
        ></p>

        <p
            x-show="wakeMode && state === 'idle' && wakeHint && !speaking"
            x-text="wakeHint"
            x-cloak
            @class(['mx-auto mt-3 max-w-md text-center leading-relaxed text-slate-400', $compact ? 'text-[10px]' : 'text-xs'])
        ></p>

        @unless ($compact)
            <p class="mx-auto mt-2 max-w-md text-center text-xs leading-relaxed text-slate-400">
                {{ __('nova_card_hint') }}
            </p>
        @endunless

        <div class="mt-4 w-full" x-show="transcript && !speaking" x-cloak>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ __('nova_recognized_speech') }}</p>
            <p class="mt-1 rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-slate-200" x-text="transcript"></p>
        </div>

        <div @class(['mt-4 flex flex-wrap items-center justify-center gap-2', $compact ? '' : 'sm:mt-5'])>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-900/40 transition hover:bg-sky-400 disabled:opacity-50"
                x-on:click="submitMessage()"
                x-bind:disabled="state === 'thinking' || speaking || (!transcript && !draft)"
            >
                <i class="fa-solid fa-paper-plane text-xs" aria-hidden="true"></i>
                <span x-text="state === 'thinking' ? @js(__('nova_state_thinking')) : @js(__('nova_ask'))"></span>
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                :class="speaking
                    ? 'border-emerald-400/60 bg-emerald-500/15 text-emerald-100'
                    : 'border-slate-600 bg-slate-900/80 text-slate-200 hover:border-sky-500/50'"
                x-show="lastReply"
                x-on:click="speakReply()"
                x-bind:disabled="speaking"
                x-cloak
            >
                <i class="fa-solid fa-volume-high text-xs" aria-hidden="true"></i>
                <span x-text="speaking ? @js(__('nova_state_speaking')) : @js(__('nova_play_reply'))"></span>
            </button>
        </div>

        <p x-show="error" x-text="error" x-cloak class="mt-3 text-center text-xs text-rose-400"></p>
    </div>
</div>
