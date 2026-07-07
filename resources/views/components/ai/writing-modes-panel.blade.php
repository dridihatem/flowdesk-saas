@props([
    'groups' => [],
    'modes' => [],
    'suggestUrl' => '',
    'speakUrl' => '',
    'proposalClients' => collect(),
    'proposalQuoteDraftUrl' => null,
    'proposalPrefillUrl' => null,
    'proposalClientContextUrl' => '',
    'defaultCurrency' => 'USD',
])

@php
    $writingAlpineConfig = [
        'suggestUrl' => $suggestUrl,
        'speakUrl' => $speakUrl,
        'csrf' => csrf_token(),
        'groups' => $groups,
        'modes' => $modes,
        'proposalClients' => $proposalClients->map(fn ($c) => [
            'id' => (string) $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'code' => $c->code,
        ])->values()->all(),
        'proposalQuoteDraftUrl' => $proposalQuoteDraftUrl,
        'proposalPrefillUrl' => $proposalPrefillUrl,
        'proposalClientContextUrl' => $proposalClientContextUrl,
        'defaultCurrency' => $defaultCurrency,
        'contextRequired' => __('ai_writing_context_required'),
        'emptyResponse' => __('Empty AI response.'),
        'requestFailed' => __('Something went wrong.'),
        'proposalSelectClient' => __('ai_proposal_select_client'),
        'proposalQuoteName' => __('ai_proposal_quote_name'),
        'proposalQuoteNameFallback' => __('Quote'),
        'proposalSpeak' => __('ai_proposal_speak_outline'),
        'proposalCreateQuote' => __('ai_proposal_create_quote'),
        'proposalGenerateLines' => __('ai_proposal_generate_lines'),
        'proposalLinesTitle' => __('ai_proposal_generated_lines'),
        'proposalLinesHint' => __('ai_proposal_lines_hint'),
        'quoteBriefRequired' => __('quote_ai_brief_required'),
        'linkPrompt' => __('ai_landing_link_prompt'),
        'imagePrompt' => __('ai_landing_image_prompt'),
        'imageAltPrompt' => __('ai_landing_image_alt_prompt'),
        'landingBuilderLabels' => [
            'elements' => __('ai_landing_panel_elements'),
            'inspector' => __('ai_landing_panel_inspector'),
            'inspectorHint' => __('ai_landing_inspector_hint'),
            'tabStyle' => __('ai_landing_tab_style'),
            'tabSettings' => __('ai_landing_tab_settings'),
            'tabLayers' => __('ai_landing_tab_layers'),
            'catSections' => __('ai_landing_cat_sections'),
            'catBasic' => __('ai_landing_cat_basic'),
            'catMedia' => __('ai_landing_cat_media'),
        ],
    ];
    $contextFieldId = 'ai_writing_mode_context';
@endphp

<div
    x-data="aiWritingModes(@js($writingAlpineConfig))"
    {{ $attributes->merge(['class' => 'space-y-6']) }}
    id="writing"
>
    <div class="flow-panel overflow-hidden p-0">
        <div class="border-b border-slate-200/80 bg-slate-50/80 px-6 py-4 dark:border-slate-700 dark:bg-slate-800/40">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('nova_legacy_tools') }}</h3>
            <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-400">{{ __('ai_writing_modes_intro') }}</p>
        </div>

        <div class="grid gap-0 lg:grid-cols-[minmax(0,17rem)_1fr]">
            {{-- Mode picker --}}
            <div class="border-b border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/30 lg:border-b-0 lg:border-e">
                @foreach ($groups as $group)
                    <div @class(['mt-4 first:mt-0' => true])>
                        <p class="px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $group['label'] }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($group['modes'] as $mode)
                                <li>
                                    <button
                                        type="button"
                                        class="flex w-full items-start gap-2.5 rounded-xl px-2.5 py-2 text-left text-sm transition"
                                        :class="selectedMode === @js($mode['mode'])
                                            ? 'bg-indigo-50 text-indigo-900 ring-1 ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-100 dark:ring-indigo-500/30'
                                            : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                        x-on:click="selectMode(@js($mode))"
                                    >
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            <i class="fa-solid {{ $mode['icon'] }} text-xs" aria-hidden="true"></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block font-semibold leading-snug">{{ $mode['title'] }}</span>
                                            <span class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">{{ $mode['credit_cost'] }} {{ __('credits') }}</span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            {{-- Editor + result --}}
            <div class="p-6">
                <template x-if="activeMod">
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-base font-semibold text-slate-900 dark:text-white" x-text="activeMod.title"></h4>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400" x-text="activeMod.description"></p>
                            <p
                                x-show="activeMod.uses_workspace_data"
                                x-cloak
                                class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-200"
                            >
                                <i class="fa-solid fa-database text-[10px]" aria-hidden="true"></i>
                                {{ __('ai_writing_uses_workspace_data') }}
                            </p>
                        </div>

                        <div
                            x-show="activeMod && (activeMod.capabilities || []).includes('client_picker')"
                            x-cloak
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Client') }}</label>
                                <select
                                    x-model="selectedClientId"
                                    x-on:change="applyClientContext()"
                                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                >
                                    <option value="">{{ __('ai_proposal_select_client') }}</option>
                                    <template x-for="client in proposalClients" :key="client.id">
                                        <option :value="client.id" x-text="client.name + (client.email ? ' — ' + client.email : '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('ai_proposal_quote_name') }}</label>
                                <input
                                    type="text"
                                    x-model="quoteName"
                                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    placeholder="{{ __('Quote title') }}"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="{{ $contextFieldId }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('ai_writing_context_label') }}</label>
                            <x-ai-voice-wrap :target-id="$contextFieldId" submit-button-id="ai_writing_generate_btn" class="mt-2">
                                <textarea
                                    id="{{ $contextFieldId }}"
                                    rows="8"
                                    x-model="context"
                                    class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    x-bind:placeholder="activeMod.placeholder"
                                ></textarea>
                            </x-ai-voice-wrap>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                id="ai_writing_generate_btn"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                x-on:click="generate()"
                                x-bind:disabled="busy"
                            >
                                <i class="fa-solid fa-wand-magic-sparkles text-xs" aria-hidden="true"></i>
                                <span x-show="!busy">{{ __('Generate with AI') }}</span>
                                <span x-show="busy" x-cloak>{{ __('Working…') }}</span>
                                <span class="text-indigo-200" x-show="activeMod && !busy" x-text="'(' + activeMod.credit_cost + ' ' + @js(__('credits')) + ')'"></span>
                            </button>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('AI-generated content — review before sending to clients.') }}</span>
                        </div>

                        <p x-show="error" x-text="error" x-cloak class="text-sm text-rose-600 dark:text-rose-400"></p>

                        {{-- Landing page: visual editor + preview --}}
                        <div x-show="isLandingMode && landingHtml" x-cloak class="space-y-4 rounded-xl border border-indigo-200/80 bg-indigo-50/30 p-4 dark:border-indigo-500/30 dark:bg-indigo-950/20">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ai_landing_editor_title') }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" x-on:click="openLandingPreview()">
                                        <i class="fa-solid fa-up-right-from-square text-[10px]" aria-hidden="true"></i>
                                        {{ __('ai_landing_preview') }}
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" x-on:click="downloadLandingHtml()">
                                        <i class="fa-solid fa-download text-[10px]" aria-hidden="true"></i>
                                        {{ __('ai_landing_download') }}
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-slate-600 dark:bg-slate-800 dark:text-indigo-300" x-on:click="copyResult()">
                                        <i class="fa-regular fa-copy text-[10px]" aria-hidden="true"></i>
                                        {{ __('Copy HTML') }}
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-600 dark:bg-slate-900">
                                <button type="button" class="rounded-md px-3 py-1.5 text-xs font-semibold" :class="landingTab === 'builder' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300'" x-on:click="setLandingTab('builder')">{{ __('ai_landing_tab_builder') }}</button>
                                <button type="button" class="rounded-md px-3 py-1.5 text-xs font-semibold" :class="landingTab === 'code' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300'" x-on:click="setLandingTab('code')">{{ __('ai_landing_tab_code') }}</button>
                            </div>

                            <div x-show="landingTab === 'builder'" x-cloak class="space-y-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ai_landing_builder_hint') }}</p>
                                <div
                                    x-ref="gjsContainer"
                                    class="flow-landing-gjs overflow-hidden rounded-xl border border-slate-300 bg-slate-100 dark:border-slate-600 dark:bg-slate-900"
                                ></div>
                            </div>

                            <div x-show="landingTab === 'code'" class="space-y-2" x-cloak>
                                <textarea
                                    rows="18"
                                    class="block w-full rounded-lg border-slate-300 font-mono text-xs shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                    x-model="landingHtml"
                                    x-on:change="syncLandingFromCode()"
                                    x-on:blur="syncLandingFromCode()"
                                ></textarea>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ai_landing_code_hint') }}</p>
                            </div>
                        </div>

                        {{-- Default text result --}}
                        <div x-show="result && !isLandingMode" x-cloak class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Suggestion') }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        x-show="activeMod && (activeMod.capabilities || []).includes('speak') && speakUrl"
                                        x-cloak
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-sky-600 hover:text-sky-500 dark:text-sky-400"
                                        x-on:click="speakResult()"
                                        x-bind:disabled="speakBusy"
                                    >
                                        <i class="fa-solid fa-volume-high" aria-hidden="true"></i>
                                        {{ __('ai_proposal_speak_outline') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                        x-on:click="copyResult()"
                                    >
                                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                        {{ __('Copy') }}
                                    </button>
                                </div>
                            </div>
                            <pre class="mt-3 max-h-[28rem] overflow-auto whitespace-pre-wrap rounded-lg bg-white p-4 text-sm text-slate-800 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700" x-text="result"></pre>

                            <div
                                x-show="activeMod && ((activeMod.capabilities || []).includes('quote_lines') || (activeMod.capabilities || []).includes('create_quote'))"
                                x-cloak
                                class="mt-4 flex flex-wrap gap-2 border-t border-slate-200 pt-4 dark:border-slate-700"
                            >
                                <button
                                    type="button"
                                    x-show="(activeMod.capabilities || []).includes('quote_lines') && proposalQuoteDraftUrl"
                                    class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
                                    x-on:click="generateQuoteLines()"
                                    x-bind:disabled="quoteLinesBusy"
                                >
                                    <i class="fa-solid fa-list-check text-[10px]" aria-hidden="true"></i>
                                    <span x-show="!quoteLinesBusy">{{ __('ai_proposal_generate_lines') }}</span>
                                    <span x-show="quoteLinesBusy" x-cloak>{{ __('Generating…') }}</span>
                                </button>
                                <button
                                    type="button"
                                    x-show="(activeMod.capabilities || []).includes('create_quote') && proposalPrefillUrl"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                                    x-on:click="openInQuote()"
                                    x-bind:disabled="quotePrefillBusy"
                                >
                                    <i class="fa-solid fa-file-circle-plus text-[10px]" aria-hidden="true"></i>
                                    <span x-show="!quotePrefillBusy">{{ __('ai_proposal_create_quote') }}</span>
                                    <span x-show="quotePrefillBusy" x-cloak>{{ __('Working…') }}</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="quoteLineItems.length > 0" x-cloak class="rounded-xl border border-violet-200/80 bg-violet-50/40 p-4 dark:border-violet-500/30 dark:bg-violet-950/20">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('ai_proposal_generated_lines') }}</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('ai_proposal_lines_hint') }}</p>
                            <ul class="mt-3 space-y-2 text-sm text-slate-800 dark:text-slate-200">
                                <template x-for="(line, index) in quoteLineItems" :key="index">
                                    <li class="rounded-lg bg-white/80 px-3 py-2 ring-1 ring-slate-200 dark:bg-slate-900/60 dark:ring-slate-700">
                                        <span x-text="line.quantity + ' × ' + line.description"></span>
                                        <span class="ms-2 text-slate-500" x-text="line.unit_major"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
