@if (! empty($flowdeskNovaVoiceNav['enabled']))
    @once('flowdesk-nova-voice-nav')
        <div
            x-data="novaVoiceNav(@js($flowdeskNovaVoiceNav))"
            x-cloak
            class="relative flex items-center"
            aria-live="polite"
        >
            <div
                x-show="showSpeakingTooltip || showWakeTooltip || error || (heard && active && !_speaking)"
                x-cloak
                class="absolute end-0 top-full z-50 mt-2 w-max max-w-sm rounded-xl border border-sky-200 bg-white px-3 py-2.5 text-xs text-slate-800 shadow-lg dark:border-sky-500/30 dark:bg-slate-900 dark:text-slate-100"
            >
                <template x-if="showSpeakingTooltip && !error">
                    <div>
                        <p class="font-semibold text-sky-700 dark:text-sky-300" x-text="speakingTooltipText"></p>
                        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400" x-text="creditsHint"></p>
                    </div>
                </template>
                <template x-if="showWakeTooltip && !error && !showSpeakingTooltip">
                    <div>
                        <p class="font-semibold text-sky-700 dark:text-sky-300" x-text="wakeTooltipText"></p>
                        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400" x-text="creditsHint"></p>
                    </div>
                </template>
                <p x-show="error" x-text="error" class="text-rose-600 dark:text-rose-400"></p>
                <p x-show="!error && !showWakeTooltip && !showSpeakingTooltip && heard" x-text="heard" class="italic text-slate-600 dark:text-slate-300"></p>
            </div>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    x-show="showStopButton"
                    x-cloak
                    class="flow-nova-topbar inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-rose-300/60 bg-rose-50/95 px-2.5 text-xs font-semibold text-rose-800 shadow-sm transition hover:bg-rose-100 dark:border-rose-500/35 dark:bg-rose-950/50 dark:text-rose-100 dark:hover:bg-rose-950/70"
                    x-on:click.stop="stopNovaResponse()"
                    x-bind:title="labels.stop || @js(__('nova_stop'))"
                    x-bind:aria-label="labels.stop || @js(__('nova_stop'))"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white">
                        <i class="fa-solid fa-stop text-[10px]" aria-hidden="true"></i>
                    </span>
                    <span class="hidden max-w-[5rem] truncate xl:inline" x-text="labels.stop || @js(__('nova_stop'))"></span>
                </button>

                <button
                    type="button"
                    class="flow-nova-topbar inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border px-2.5 text-xs font-semibold shadow-sm transition dark:bg-slate-900/80 dark:text-slate-100"
                    :class="_listeningPaused
                        ? 'border-rose-300/60 bg-rose-50/95 text-rose-900 hover:bg-rose-100 dark:border-rose-500/35 dark:bg-rose-950/50 dark:text-rose-100 dark:hover:bg-rose-950/70'
                        : 'border-sky-300/50 bg-white/90 text-slate-800 hover:bg-sky-50 dark:border-sky-500/30 dark:hover:bg-slate-800'"
                    x-on:click="toggleMic()"
                    x-bind:title="supported ? (_listeningPaused ? (labels.notListening || status) : status) : @js(__('ai_voice_unsupported'))"
                    aria-label="{{ config('flowdesk.ai_assistant_brand_name', 'Nova') }}"
                >
                    <span
                        class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-white"
                        :class="[
                            _listeningPaused
                                ? 'bg-gradient-to-br from-rose-500 to-red-600'
                                : 'bg-gradient-to-br from-sky-400 to-indigo-600',
                            { 'animate-pulse': supported && recognitionActive && !error && !showStopButton && !_listeningPaused },
                        ]"
                    >
                        <i class="fa-solid text-xs" :class="_listeningPaused ? 'fa-microphone-slash' : 'fa-microphone'" aria-hidden="true"></i>
                    </span>
                    <span class="hidden max-w-[8rem] truncate xl:inline" :class="_listeningPaused ? 'text-rose-800 dark:text-rose-100' : 'text-slate-600 dark:text-slate-300'" x-text="status"></span>
                </button>
            </div>
        </div>
    @endonce
@endif
