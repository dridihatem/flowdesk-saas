<x-admin-layout>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-flow.page-header
            :title="__('Platform settings')"
            :description="__('Configure AI thinking providers (analysis, chat, tasks), Nova voice (TTS), and currency rates for the platform.')"
        />

        <a
            href="{{ route('admin.platform-settings.export-sql') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-file-arrow-down text-xs" aria-hidden="true"></i>
            <span>{{ __('Export SQL') }}</span>
        </a>
    </div>

    <div class="mt-6 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-5 text-sm text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
        <p class="font-semibold">{{ __('Platform setup checklist') }}</p>
        <ol class="mt-3 list-decimal space-y-2 ps-5">
            <li>{!! __('Platform setup checklist migrate') !!}</li>
            <li>{{ __('Platform setup checklist keys') }}</li>
            <li>{{ __('Platform setup checklist provider') }}</li>
        </ol>
    </div>

    <form method="POST" action="{{ route('admin.platform-settings.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('AI thinking (analysis, chat, tasks)') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('AI thinking help') }}</p>
            <div class="mt-4 max-w-md">
                <x-input-label for="ai_provider" :value="__('Thinking provider preference')" />
                <select
                    id="ai_provider"
                    name="ai_provider"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                    <option value="auto" @selected(old('ai_provider', $settings->ai_provider ?? 'auto') === 'auto')>{{ __('Automatic: Claude, then OpenAI, then Google (Gemini)') }}</option>
                    <option value="anthropic" @selected(old('ai_provider', $settings->ai_provider ?? 'auto') === 'anthropic')>{{ __('Anthropic (Claude) only') }}</option>
                    <option value="openai" @selected(old('ai_provider', $settings->ai_provider ?? 'auto') === 'openai')>{{ __('OpenAI only') }}</option>
                    <option value="google" @selected(old('ai_provider', $settings->ai_provider ?? 'auto') === 'google')>{{ __('Google (Gemini) only') }}</option>
                </select>
                <x-input-error :messages="$errors->get('ai_provider')" class="mt-2" />
            </div>
        </div>

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Nova voice (text-to-speech)') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Nova voice help') }}</p>
            <div class="mt-4 max-w-md">
                <x-input-label for="tts_provider" :value="__('Voice provider')" />
                <select
                    id="tts_provider"
                    name="tts_provider"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                    <option value="auto" @selected(old('tts_provider', $settings->tts_provider ?? 'auto') === 'auto')>{{ __('Automatic: Microsoft Edge (free), then Gemini, then OpenAI') }}</option>
                    <option value="edge" @selected(old('tts_provider', $settings->tts_provider ?? 'auto') === 'edge')>{{ __('Microsoft Edge TTS (free, no API key)') }}</option>
                    <option value="google" @selected(old('tts_provider', $settings->tts_provider ?? 'auto') === 'google')>{{ __('Google Gemini TTS only') }}</option>
                    <option value="openai" @selected(old('tts_provider', $settings->tts_provider ?? 'auto') === 'openai')>{{ __('OpenAI TTS only') }}</option>
                    <option value="browser" @selected(old('tts_provider', $settings->tts_provider ?? 'auto') === 'browser')>{{ __('Browser voice only (no server synthesis)') }}</option>
                </select>
                <x-input-error :messages="$errors->get('tts_provider')" class="mt-2" />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flow-panel p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Anthropic (Claude)') }}</h3>
                <p class="mt-1 text-xs text-slate-600">{{ __('Anthropic thinking help') }}</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="anthropic_api_key" :value="__('Anthropic API key')" />
                        <x-text-input id="anthropic_api_key" name="anthropic_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="clear_anthropic_api_key" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('clear_anthropic_api_key')) />
                            <span>{{ __('Remove stored Anthropic API key') }}</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave the key field blank to keep the current key. Enter a new key to replace it.') }}</p>
                        <x-input-error :messages="$errors->get('anthropic_api_key')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="claude_model" :value="__('Claude model (optional)')" />
                        <x-text-input id="claude_model" name="claude_model" type="text" class="mt-1 block w-full" :value="old('claude_model', $settings->claude_model ?? '')" placeholder="claude-3-5-haiku-20241022" />
                        <x-input-error :messages="$errors->get('claude_model')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flow-panel p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('OpenAI') }}</h3>
                <p class="mt-1 text-xs text-slate-600">{{ __('OpenAI thinking help') }}</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="openai_api_key" :value="__('OpenAI API key')" />
                        <x-text-input id="openai_api_key" name="openai_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="clear_openai_api_key" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('clear_openai_api_key')) />
                            <span>{{ __('Remove stored OpenAI API key') }}</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave the key field blank to keep the current key. Enter a new key to replace it.') }}</p>
                        <x-input-error :messages="$errors->get('openai_api_key')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="openai_model" :value="__('OpenAI model (optional)')" />
                        <x-text-input id="openai_model" name="openai_model" type="text" class="mt-1 block w-full" :value="old('openai_model', $settings->openai_model ?? '')" placeholder="gpt-4o-mini" />
                        <x-input-error :messages="$errors->get('openai_model')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flow-panel p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Google (Gemini / AI Studio)') }}</h3>
                <p class="mt-1 text-xs text-slate-600">{{ __('Google thinking help') }}</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="google_api_key" :value="__('Google AI API key')" />
                        <x-text-input id="google_api_key" name="google_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="clear_google_api_key" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('clear_google_api_key')) />
                            <span>{{ __('Remove stored Google API key') }}</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave the key field blank to keep the current key. Enter a new key to replace it.') }}</p>
                        <x-input-error :messages="$errors->get('google_api_key')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="gemini_model" :value="__('Gemini chat model (optional)')" />
                        <x-text-input id="gemini_model" name="gemini_model" type="text" class="mt-1 block w-full" :value="old('gemini_model', $settings->gemini_model ?? '')" placeholder="gemini-2.0-flash" />
                        <p class="mt-1 text-xs text-slate-500">
                            <a href="https://ai.google.dev/gemini-api/docs/models/gemini" class="text-emerald-800 underline dark:text-emerald-300" target="_blank" rel="noopener">{{ __('Gemini model list (docs)') }}</a>
                        </p>
                        <x-input-error :messages="$errors->get('gemini_model')" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Voice synthesis settings') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Voice synthesis settings help') }}</p>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('Microsoft Edge TTS') }}</h4>
                    <p class="mt-1 text-xs text-slate-600">{{ __('Edge TTS help') }}</p>

                    <div class="mt-4">
                        <x-input-label for="edge_tts_voice" :value="__('Edge voice (optional override)')" />
                        <select
                            id="edge_tts_voice"
                            name="edge_tts_voice"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        >
                            <option value="" @selected(old('edge_tts_voice', $settings->edge_tts_voice ?? '') === '')>{{ __('Automatic by app language (FR, EN, ES, AR)') }}</option>
                            @foreach (\App\Services\MicrosoftEdgeTextToSpeechService::VOICES as $voice)
                                <option value="{{ $voice }}" @selected(old('edge_tts_voice', $settings->edge_tts_voice ?? '') === $voice)>{{ $voice }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('edge_tts_voice')" class="mt-2" />
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('Google Gemini TTS') }}</h4>
                    <p class="mt-1 text-xs text-slate-600">{{ __('Gemini TTS help') }}</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Premium TTS subscription help') }}</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="gemini_tts_model" :value="__('Gemini TTS model (optional)')" />
                            <x-text-input id="gemini_tts_model" name="gemini_tts_model" type="text" class="mt-1 block w-full" :value="old('gemini_tts_model', $settings->gemini_tts_model ?? '')" placeholder="gemini-2.5-flash-preview-tts" />
                            <x-input-error :messages="$errors->get('gemini_tts_model')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gemini_tts_voice" :value="__('Gemini voice name')" />
                            <select
                                id="gemini_tts_voice"
                                name="gemini_tts_voice"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            >
                                @foreach (['Kore', 'Puck', 'Charon', 'Aoede', 'Fenrir', 'Leda', 'Orus', 'Zephyr', 'Achird', 'Sulafat'] as $voice)
                                    <option value="{{ $voice }}" @selected(old('gemini_tts_voice', $settings->gemini_tts_voice ?? 'Kore') === $voice)>{{ $voice }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gemini_tts_voice')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                    <h4 class="text-sm font-semibold text-slate-900">{{ __('OpenAI TTS') }}</h4>
                    <p class="mt-1 text-xs text-slate-600">{{ __('OpenAI TTS help') }}</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('Premium TTS subscription help') }}</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="openai_tts_voice" :value="__('OpenAI voice')" />
                            <select
                                id="openai_tts_voice"
                                name="openai_tts_voice"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            >
                                @foreach (['nova', 'shimmer', 'alloy', 'echo', 'fable', 'onyx', 'coral', 'sage', 'ash', 'ballad', 'verse'] as $voice)
                                    <option value="{{ $voice }}" @selected(old('openai_tts_voice', $settings->openai_tts_voice ?? 'nova') === $voice)>{{ ucfirst($voice) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('openai_tts_voice')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="openai_tts_model" :value="__('OpenAI TTS model (optional)')" />
                            <x-text-input id="openai_tts_model" name="openai_tts_model" type="text" class="mt-1 block w-full" :value="old('openai_tts_model', $settings->openai_tts_model ?? '')" placeholder="tts-1-hd" />
                            <x-input-error :messages="$errors->get('openai_tts_model')" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Currency rates') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('1 base currency = rate × quote currency.') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin_exchange_rate_all_currencies_help') }}</p>

            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <p class="text-sm font-semibold text-emerald-950 dark:text-emerald-100">{{ __('admin_exchange_rate_qatar_title') }}</p>
                <p class="mt-1 text-xs text-emerald-900/90 dark:text-emerald-200/90">{{ __('admin_exchange_rate_qatar_lead') }}</p>
                <p class="mt-2 text-xs text-emerald-800 dark:text-emerald-200">{{ __('admin_exchange_rate_qatar_example') }}</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <div class="min-w-[28rem] space-y-3">
                    <div class="grid grid-cols-3 gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>{{ __('Base currency') }}</span>
                        <span>{{ __('Quote currency') }}</span>
                        <span>{{ __('Rate') }}</span>
                    </div>

                    @php($rows = old('rates') ?? $rateRows)

                    @foreach ($rows as $i => $row)
                        @php($quote = $row['quote_currency'] ?? '')
                        @php($isQar = $quote === 'QAR')
                        <div @class([
                            'grid grid-cols-3 gap-3 rounded-lg p-2',
                            'border border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/20' => $isQar || ($row['highlight'] ?? false),
                            'bg-slate-50 dark:bg-slate-900/40' => ! $isQar && ! ($row['highlight'] ?? false),
                        ])>
                            <input type="hidden" name="rates[{{ $i }}][base_currency]" value="{{ $row['base_currency'] ?? 'USD' }}" />
                            <x-text-input class="w-full bg-slate-100 dark:bg-slate-800" value="{{ $row['base_currency'] ?? 'USD' }}" readonly />
                            <input type="hidden" name="rates[{{ $i }}][quote_currency]" value="{{ $quote }}" />
                            <x-text-input @class([
                                'w-full bg-slate-100 dark:bg-slate-800',
                                'font-semibold' => $isQar,
                            ]) value="{{ config('flowdesk.currency_labels')[$quote] ?? $quote }}" readonly />
                            <div>
                                <x-text-input
                                    id="rate_{{ $quote }}"
                                    name="rates[{{ $i }}][rate]"
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    class="w-full"
                                    :value="$row['rate'] ?? ''"
                                    placeholder="{{ $isQar ? '3.64' : '1.00' }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('rates.'.$i.'.rate')" class="mt-1" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('rates')" class="mt-2" />
            </div>
        </div>

        <div class="flex justify-end">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.querySelectorAll('input[name^="rates["][name$="[rate]"]').forEach((input) => {
                const message = @json(__('admin_exchange_rate_invalid'));

                input.addEventListener('invalid', () => {
                    input.setCustomValidity(message);
                });

                input.addEventListener('input', () => {
                    input.setCustomValidity('');
                });
            });
        </script>
    @endpush
</x-admin-layout>
