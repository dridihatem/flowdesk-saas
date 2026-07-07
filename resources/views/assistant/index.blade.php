@php
    $brandName = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');
    $novaPayload = [
        'assistant_name' => $assistantName,
        'chat_url' => $chatUrl,
        'credit_cost' => $creditCost,
        'summary' => $summary,
        'assistant_url' => route('assistant.index'),
    ];
    $initialTab = request()->boolean('writing') ? 'writing' : 'chat';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
            {{ $brandName }}
        </h2>
    </x-slot>

    <div
        class="py-10"
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
        "
    >
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="$brandName"
                :description="__('nova_assistant_page_lead')"
            />

            <nav class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-slate-200/80 bg-white/90 p-1.5 shadow-sm dark:border-slate-700 dark:bg-slate-900/60" aria-label="{{ __('nova_assistant_tabs_label') }}">
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition sm:flex-none"
                    :class="tab === 'chat'
                        ? 'bg-sky-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                    x-on:click="setTab('chat')"
                >
                    <i class="fa-solid fa-microphone-lines text-xs" aria-hidden="true"></i>
                    {{ __('nova_tab_chat') }}
                </button>
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition sm:flex-none"
                    :class="tab === 'writing'
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                    x-on:click="setTab('writing')"
                >
                    <i class="fa-solid fa-pen-nib text-xs" aria-hidden="true"></i>
                    {{ __('nova_tab_writing') }}
                </button>
            </nav>

            <div x-show="tab === 'chat'" x-cloak class="space-y-6">
                <x-ai.nova-help :credit-cost="$creditCost" />

                <x-ai.nova-shell :nova="$novaPayload" :enable-wake-word="true">
                    <div class="grid gap-6 xl:grid-cols-3">
                        <div class="space-y-6 xl:col-span-2">
                            <x-ai.assistant-card :assistant-name="$assistantName" />
                            <x-ai.chat-panel />
                        </div>

                        <div class="space-y-6">
                            <x-ai.summary-widget :summary="$summary" />
                            <x-ai.conversation-history :conversations="$conversations" />
                        </div>
                    </div>
                </x-ai.nova-shell>
            </div>

            <div x-show="tab === 'writing'" x-cloak>
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
    </div>
</x-app-layout>
