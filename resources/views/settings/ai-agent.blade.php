<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('workspace_ai_agent_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('workspace_ai_agent_intro') }}</p>

            @if ($form['uses_workspace_agent'])
                <div class="mt-4 rounded-xl border border-indigo-200/80 bg-indigo-50/90 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/50 dark:text-indigo-100">
                    {{ __('workspace_ai_agent_active_notice') }}
                </div>
            @endif

            <div class="mt-8 rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50 sm:p-8">
                <form method="POST" action="{{ route('settings.ai-agent.update') }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="flex items-start gap-3 text-sm text-slate-800 dark:text-slate-200">
                            <input
                                type="checkbox"
                                name="enabled"
                                value="1"
                                @checked(old('enabled', $form['enabled']))
                                class="mt-0.5 rounded border-slate-300 text-indigo-600"
                            />
                            <span>
                                <strong>{{ __('workspace_ai_agent_enable') }}</strong>
                                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('workspace_ai_agent_enable_help') }}</span>
                            </span>
                        </label>
                    </div>

                    <div>
                        <x-input-label for="ai_provider" :value="__('Thinking provider preference')" />
                        <select
                            id="ai_provider"
                            name="ai_provider"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        >
                            <option value="auto" @selected(old('ai_provider', $form['ai_provider']) === 'auto')>{{ __('Automatic: Claude, then OpenAI, then Google (Gemini)') }}</option>
                            <option value="anthropic" @selected(old('ai_provider', $form['ai_provider']) === 'anthropic')>{{ __('Anthropic (Claude) only') }}</option>
                            <option value="openai" @selected(old('ai_provider', $form['ai_provider']) === 'openai')>{{ __('OpenAI only') }}</option>
                            <option value="google" @selected(old('ai_provider', $form['ai_provider']) === 'google')>{{ __('Google (Gemini) only') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('ai_provider')" class="mt-2" />
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Anthropic (Claude)') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('workspace_ai_agent_key_help') }}</p>
                        <div class="mt-3 space-y-3">
                            <div>
                                <x-input-label for="anthropic_api_key" :value="__('API key')" />
                                <x-text-input id="anthropic_api_key" name="anthropic_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                                @if ($form['has_anthropic_key'])
                                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('workspace_ai_agent_key_stored') }}</p>
                                @endif
                                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" name="clear_anthropic_api_key" value="1" class="rounded border-slate-300" />
                                    {{ __('workspace_ai_agent_clear_key') }}
                                </label>
                            </div>
                            <div>
                                <x-input-label for="claude_model" :value="__('Claude model (optional)')" />
                                <x-text-input id="claude_model" name="claude_model" type="text" class="mt-1 block w-full" :value="old('claude_model', $form['claude_model'])" placeholder="claude-3-5-haiku-20241022" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('OpenAI') }}</h3>
                        <div class="mt-3 space-y-3">
                            <div>
                                <x-input-label for="openai_api_key" :value="__('API key')" />
                                <x-text-input id="openai_api_key" name="openai_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                                @if ($form['has_openai_key'])
                                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('workspace_ai_agent_key_stored') }}</p>
                                @endif
                                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" name="clear_openai_api_key" value="1" class="rounded border-slate-300" />
                                    {{ __('workspace_ai_agent_clear_key') }}
                                </label>
                            </div>
                            <div>
                                <x-input-label for="openai_model" :value="__('OpenAI model (optional)')" />
                                <x-text-input id="openai_model" name="openai_model" type="text" class="mt-1 block w-full" :value="old('openai_model', $form['openai_model'])" placeholder="gpt-4o-mini" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Google (Gemini)') }}</h3>
                        <div class="mt-3 space-y-3">
                            <div>
                                <x-input-label for="google_api_key" :value="__('API key')" />
                                <x-text-input id="google_api_key" name="google_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                                @if ($form['has_google_key'])
                                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('workspace_ai_agent_key_stored') }}</p>
                                @endif
                                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" name="clear_google_api_key" value="1" class="rounded border-slate-300" />
                                    {{ __('workspace_ai_agent_clear_key') }}
                                </label>
                            </div>
                            <div>
                                <x-input-label for="gemini_model" :value="__('Gemini chat model (optional)')" />
                                <x-text-input id="gemini_model" name="gemini_model" type="text" class="mt-1 block w-full" :value="old('gemini_model', $form['gemini_model'])" placeholder="gemini-2.0-flash" />
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('workspace_ai_agent_tts_note') }}</p>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
