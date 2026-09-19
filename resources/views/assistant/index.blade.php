@php
    $novaPayload = [
        'assistant_name' => $assistantName,
        'chat_url' => $chatUrl,
        'agent_url' => $agentUrl ?? route('assistant.agent.run'),
        'legacy_chat_url' => $legacyChatUrl ?? $chatUrl,
        'use_agent' => $useAgent ?? (bool) config('flowdesk.nova_agent_ui_enabled', true),
        'current_page' => $flowdeskNovaPageContext['current_page'] ?? 'assistant.index',
        'current_entity' => $flowdeskNovaPageContext['current_entity'] ?? null,
        'company_id' => (string) (auth()->user()?->company_id ?? ''),
        'credit_cost' => $creditCost,
        'summary' => $summary,
        'assistant_url' => route('assistant.index'),
        'fullscreen' => true,
    ];
    $initialTab = request()->boolean('writing') ? 'writing' : 'chat';
@endphp

<x-app-layout>
    <div
        class="nova-neural-page"
        x-data="{
            tab: @js($initialTab),
            setTab(next) {
                this.tab = next;
                const hash = next === 'writing' ? '#writing' : '';
                if (window.history?.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname + window.location.search + hash);
                }
            }
        }"
        x-init="
            if (window.location.hash === '#writing' || window.location.hash.startsWith('#mode=')) {
                tab = 'writing';
            }
            document.documentElement.classList.add('nova-neural-page-active');
            document.body.classList.add('nova-neural-page-active');
        "
    >
        <nav class="nova-neural-page__tabs" aria-label="{{ __('nova_assistant_tabs_label') }}">
            <button
                type="button"
                class="nova-neural-page__tab"
                :class="tab === 'chat' ? 'is-active' : ''"
                x-on:click="setTab('chat')"
            >
                <i class="fa-solid fa-microphone-lines text-xs" aria-hidden="true"></i>
                {{ __('nova_tab_chat') }}
            </button>
            <button
                type="button"
                class="nova-neural-page__tab"
                :class="tab === 'writing' ? 'is-active' : ''"
                x-on:click="setTab('writing')"
            >
                <i class="fa-solid fa-pen-nib text-xs" aria-hidden="true"></i>
                {{ __('nova_tab_writing') }}
            </button>
        </nav>

        <div x-show="tab === 'chat'" x-cloak class="nova-neural-page__stage">
            <x-ai.nova-shell :nova="$novaPayload" :enable-wake-word="true" class="h-full min-h-0">
                <x-ai.nova-neural-stage
                    :assistant-name="$assistantName"
                    :summary="$summary"
                    :conversations="$conversations"
                    :credit-cost="$creditCost"
                    class="h-full"
                />
            </x-ai.nova-shell>
        </div>

        <div x-show="tab === 'writing'" x-cloak class="nova-neural-page__writing">
            <x-ai.writing-modes-panel
                :groups="$writingModeGroups"
                :modes="$writingModes"
                :suggest-url="$suggestUrl"
                :speak-url="$speakUrl"
                :proposal-clients="$proposalClients"
                :proposal-quote-draft-url="$proposalQuoteDraftUrl"
                :proposal-prefill-url="$proposalPrefillUrl"
                :proposal-client-context-url="$proposalClientContextUrl"
                :default-currency="$defaultCurrency"
            />
        </div>
    </div>
</x-app-layout>
