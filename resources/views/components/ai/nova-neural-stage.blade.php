@props([
    'assistantName',
    'summary' => [],
    'conversations' => collect(),
    'creditCost' => 0,
])

@php
    $brand = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');
@endphp

<div
    {{ $attributes->merge(['class' => 'nova-neural-stage']) }}
    :class="{
        'nova-neural-stage--listening': cardState === 'listening',
        'nova-neural-stage--speaking': cardState === 'speaking',
        'nova-neural-stage--thinking': cardState === 'thinking' || cardState === 'responding',
        'nova-neural-stage--wake': wakeMode && cardState === 'idle',
    }"
>
    <div class="nova-neural-stage__bg" aria-hidden="true">
        <canvas data-nova-neural-canvas class="h-full w-full"></canvas>
        <div class="nova-neural-stage__vignette"></div>
        <div class="nova-neural-stage__grid"></div>
    </div>

    <div class="nova-neural-stage__chrome">
        <div class="nova-neural-stage__brand">
            <p class="nova-neural-stage__brand-mark">{{ $assistantName }}</p>
            <p class="nova-neural-stage__brand-sub" x-text="stateLabel"></p>
        </div>

        <div class="nova-neural-stage__chrome-actions">
            <div
                class="nova-neural-stage__status"
                :class="{
                    'is-active': voiceActive || cardState === 'thinking',
                    'is-listening': cardState === 'listening',
                    'is-speaking': cardState === 'speaking',
                }"
                :aria-label="stateLabel"
            >
                <span class="nova-neural-stage__status-ring" aria-hidden="true"></span>
                <span class="nova-neural-stage__status-dot" aria-hidden="true"></span>
                <span class="nova-neural-stage__status-label" x-text="statusBadgeLabel"></span>
            </div>

            <button
                type="button"
                class="nova-neural-stage__icon-btn"
                x-on:click="panel = panel === 'ops' ? 'talk' : 'ops'"
                :aria-expanded="panel === 'ops'"
                :aria-label="@js(__('nova_summary_title'))"
                title="{{ __('nova_summary_title') }}"
            >
                <i class="fa-solid fa-chart-simple text-sm" aria-hidden="true"></i>
            </button>

            <button
                type="button"
                class="nova-neural-stage__icon-btn"
                x-on:click="panel = panel === 'help' ? 'talk' : 'help'"
                :aria-expanded="panel === 'help'"
                :aria-label="@js(__('nova_help_title'))"
                title="{{ __('nova_help_title') }}"
            >
                <i class="fa-solid fa-circle-info text-sm" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="nova-neural-stage__body">
        {{-- Orb / talk surface --}}
        <section class="nova-neural-stage__orb-zone" aria-label="{{ $brand }}">
            <div class="nova-neural-stage__orb">
                <span class="nova-neural-stage__orbit nova-neural-stage__orbit--outer" :class="{ 'is-active': voiceActive || wakeMode }" aria-hidden="true"></span>
                <span class="nova-neural-stage__orbit nova-neural-stage__orbit--mid" :class="{ 'is-active': cardState === 'listening' || cardState === 'speaking' || cardState === 'thinking' }" aria-hidden="true"></span>
                <span class="nova-neural-stage__orbit nova-neural-stage__orbit--inner" :class="{ 'is-active': cardState === 'speaking' || cardState === 'thinking' }" aria-hidden="true"></span>

                <button
                    type="button"
                    class="nova-neural-stage__mic"
                    :class="{
                        'is-listening': cardState === 'listening',
                        'is-speaking': cardState === 'speaking',
                        'is-thinking': cardState === 'thinking',
                    }"
                    x-on:click="toggleVoice()"
                    x-bind:disabled="!voiceSupported || state === 'thinking' || speaking"
                    :aria-label="cardState === 'listening' ? @js(__('nova_stop_listening')) : @js(__('nova_start_listening'))"
                >
                    <i
                        class="fa-solid"
                        :class="cardState === 'listening' ? 'fa-stop' : (cardState === 'speaking' ? 'fa-volume-high' : 'fa-microphone')"
                        aria-hidden="true"
                    ></i>
                </button>
            </div>

            <div
                class="nova-neural-stage__waves"
                :class="{ 'is-active': voiceActive }"
                aria-hidden="true"
            >
                @foreach (range(1, 9) as $bar)
                    <span class="nova-neural-stage__wave" style="--nova-wave-index: {{ $bar }}"></span>
                @endforeach
            </div>

            <p
                x-show="!voiceSupported"
                x-cloak
                class="nova-neural-stage__hint nova-neural-stage__hint--warn"
                x-text="voiceUnavailableMessage"
            ></p>

            <p
                x-show="wakeMode && state === 'idle' && wakeHint && !speaking"
                x-text="wakeHint"
                x-cloak
                class="nova-neural-stage__hint"
            ></p>

            <p
                x-show="transcript && !speaking"
                x-cloak
                class="nova-neural-stage__transcript-live"
                x-text="transcript"
            ></p>

            <p x-show="error" x-text="error" x-cloak class="nova-neural-stage__hint nova-neural-stage__hint--error"></p>

            <div class="nova-neural-stage__quick-actions">
                <button
                    type="button"
                    class="nova-neural-stage__cta"
                    x-on:click="submitMessage()"
                    x-bind:disabled="state === 'thinking' || speaking || (!transcript && !draft)"
                >
                    <i class="fa-solid fa-paper-plane text-xs" aria-hidden="true"></i>
                    <span x-text="state === 'thinking' ? @js(__('nova_state_thinking')) : @js(__('nova_ask'))"></span>
                </button>
                <button
                    type="button"
                    class="nova-neural-stage__cta nova-neural-stage__cta--ghost"
                    x-show="lastReply"
                    x-on:click="speakReply()"
                    x-bind:disabled="speaking"
                    x-cloak
                >
                    <i class="fa-solid fa-volume-high text-xs" aria-hidden="true"></i>
                    <span x-text="speaking ? @js(__('nova_state_speaking')) : @js(__('nova_play_reply'))"></span>
                </button>
            </div>
        </section>

        {{-- Conversation + activity --}}
        <section class="nova-neural-stage__stream" aria-label="{{ __('nova_chat_title') }}">
            <div class="nova-neural-stage__stream-head">
                <h2>{{ __('nova_chat_title') }}</h2>
                <p x-show="activities.length > 0" x-cloak class="nova-neural-stage__activity-live">
                    <span class="nova-neural-stage__activity-pulse" aria-hidden="true"></span>
                    <span x-text="activities[activities.length - 1]?.message || ''"></span>
                </p>
            </div>

            <div class="nova-neural-stage__stream-scroll" x-ref="chatScroll">
                <template x-if="messages.length === 0 && activities.length === 0">
                    <p class="nova-neural-stage__empty">{{ __('nova_chat_empty') }}</p>
                </template>

                <template x-for="(msg, idx) in messages" :key="idx">
                    <article
                        class="nova-neural-stage__bubble"
                        :class="msg.role === 'assistant' ? 'is-assistant' : 'is-user'"
                    >
                        <p class="nova-neural-stage__bubble-role" x-text="msg.role === 'assistant' ? assistantName : @js(__('You'))"></p>
                        <p class="nova-neural-stage__bubble-text" x-text="displayMessage(msg.content)"></p>
                    </article>
                </template>

                <div x-show="activities.length > 0" x-cloak class="nova-neural-stage__activity">
                    <p class="nova-neural-stage__activity-title" x-text="labels.activityTitle || @js(__('nova_activity_title'))"></p>
                    <ul>
                        <template x-for="(act, aidx) in activities" :key="act.id || aidx">
                            <li>
                                <span
                                    class="nova-neural-stage__activity-dot"
                                    :class="act.status === 'done' ? 'is-done' : 'is-live'"
                                ></span>
                                <span x-text="act.message"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <div
                    x-show="clarificationOptions && clarificationOptions.length"
                    x-cloak
                    class="nova-neural-stage__clarify"
                >
                    <p>{{ __('nova_clarify_prompt') }}</p>
                    <div class="nova-neural-stage__clarify-options">
                        <template x-for="(opt, oidx) in (clarificationOptions || [])" :key="opt.id || oidx">
                            <button
                                type="button"
                                class="nova-neural-stage__chip"
                                x-on:click="chooseClarification(opt)"
                                x-text="opt.label || opt.id"
                            ></button>
                        </template>
                    </div>
                </div>

                <div
                    x-show="pendingConfirmation"
                    x-cloak
                    class="nova-neural-stage__confirm"
                >
                    <p x-text="pendingConfirmation?.message || @js(__('nova_confirm_prompt'))"></p>
                    <div class="nova-neural-stage__confirm-actions">
                        <button
                            type="button"
                            class="nova-neural-stage__cta"
                            x-on:click="confirmPendingAction(true)"
                            x-text="labels.confirm || @js(__('nova_confirm_action'))"
                        ></button>
                        <button
                            type="button"
                            class="nova-neural-stage__cta nova-neural-stage__cta--ghost"
                            x-on:click="confirmPendingAction(false)"
                            x-text="labels.cancel || @js(__('nova_cancel_action'))"
                        ></button>
                    </div>
                </div>
            </div>

            <form class="nova-neural-stage__composer" x-on:submit.prevent="submitMessage()">
                <input
                    type="text"
                    x-model="draft"
                    class="nova-neural-stage__input"
                    placeholder="{{ __('nova_input_placeholder') }}"
                    x-bind:disabled="state === 'thinking'"
                    autocomplete="off"
                />
                <button
                    type="submit"
                    class="nova-neural-stage__send"
                    x-bind:disabled="state === 'thinking' || !(draft || '').trim()"
                >
                    <span x-show="state !== 'thinking'">{{ __('Send') }}</span>
                    <span x-show="state === 'thinking'" x-cloak>{{ __('nova_state_thinking') }}</span>
                </button>
            </form>
        </section>
    </div>

    {{-- Ops drawer: summary + history --}}
    <div
        class="nova-neural-stage__drawer"
        x-show="panel === 'ops'"
        x-cloak
        x-transition.opacity.duration.200ms
    >
        <button type="button" class="nova-neural-stage__drawer-backdrop" x-on:click="panel = 'talk'" aria-label="Close"></button>
        <aside class="nova-neural-stage__drawer-inner" role="dialog" aria-label="{{ __('nova_summary_title') }}">
            <div class="nova-neural-stage__drawer-head">
                <h3>{{ __('nova_summary_title') }}</h3>
                <button type="button" class="nova-neural-stage__icon-btn" x-on:click="panel = 'talk'" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <x-ai.summary-widget :summary="$summary" class="nova-neural-stage__widget" />
            <x-ai.conversation-history :conversations="$conversations" class="nova-neural-stage__widget" />
        </aside>
    </div>

    {{-- Help drawer --}}
    <div
        class="nova-neural-stage__drawer"
        x-show="panel === 'help'"
        x-cloak
        x-transition.opacity.duration.200ms
    >
        <button type="button" class="nova-neural-stage__drawer-backdrop" x-on:click="panel = 'talk'" aria-label="Close"></button>
        <aside class="nova-neural-stage__drawer-inner nova-neural-stage__drawer-inner--help" role="dialog" aria-label="{{ __('nova_help_title') }}">
            <div class="nova-neural-stage__drawer-head">
                <h3>{{ __('nova_help_title') }}</h3>
                <button type="button" class="nova-neural-stage__icon-btn" x-on:click="panel = 'talk'" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <x-ai.nova-help :credit-cost="$creditCost" class="nova-neural-stage__help" />
        </aside>
    </div>
</div>
