<x-app-layout>
    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-8">
            <x-flow.page-header
                :title="__('Website embed, API token & tracking')"
                :description="__('widget_embed_page_description')"
            />

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ── API token card ────────────────────────────────── --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Company API token (embed)') }}</h3>

                <div class="mt-4" x-data="{ showToken: false, copied: false, tokenVal: '{{ e($apiTokenPlain ?? '') }}' }">
                    @if ($apiTokenPlain)
                        @if ($revealedToken)
                            {{-- Token was JUST generated --}}
                            <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/60 p-3 dark:border-emerald-800/40 dark:bg-emerald-950/30">
                                <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ __('profile_embed_token_generated_note') }}</p>
                            </div>
                        @endif
                        <label for="flowdesk-embed-api-token-pw" class="sr-only">{{ __('Company API token (embed)') }}</label>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                            <div class="relative min-w-0 flex-1">
                                <input
                                    id="flowdesk-embed-api-token-pw"
                                    x-ref="tokenValue"
                                    type="password"
                                    readonly
                                    x-bind:value="tokenVal"
                                    x-show="!showToken"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                    autocomplete="off"
                                    spellcheck="false"
                                />
                                <input
                                    type="text"
                                    readonly
                                    x-bind:value="tokenVal"
                                    x-show="showToken"
                                    x-cloak
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                                    autocomplete="off"
                                    spellcheck="false"
                                    tabindex="-1"
                                />
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-offset-slate-900"
                                    x-on:click="showToken = !showToken"
                                    x-bind:aria-pressed="showToken"
                                >
                                    <span x-show="showToken" x-cloak>{{ __('widget_embed_hide_token') }}</span>
                                    <span x-show="!showToken">{{ __('widget_embed_show_token') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-offset-slate-900"
                                    x-on:click="navigator.clipboard.writeText(tokenVal); copied = true; clearTimeout(window._flowdeskCopyT); window._flowdeskCopyT = setTimeout(() => { copied = false }, 2000)"
                                >
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('widget_embed_token_copied') }}</span>
                                </button>
                            </div>
                        </div>
                    @elseif ($hasApiToken)
                        {{-- Legacy token: plaintext was never stored --}}
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            {{ __('profile_embed_token_legacy_note') }}
                        </p>
                        @if ($apiTokenHint)
                            <div class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200/80 bg-slate-50/80 px-3 py-2 dark:border-slate-700/80 dark:bg-slate-800/50">
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Token hint:') }}</span>
                                <span class="font-mono text-sm font-medium text-slate-800 dark:text-slate-200">fd_live_…{{ $apiTokenHint }}</span>
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('If you lost the token, generate a new one below. The previous token will stop working.') }}</p>
                    @else
                        {{-- No token at all --}}
                        <div class="rounded-lg border border-dashed border-amber-300/80 bg-amber-50/60 p-4 dark:border-amber-700/50 dark:bg-amber-950/30">
                            <p class="text-sm text-amber-800 dark:text-amber-200/90">
                                {{ __('No company API token yet. Generate one — it is required for the embeddable widget.') }}
                            </p>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('settings.widget-embed.regenerate-token') }}" class="mt-6" onsubmit="return confirm({{ json_encode(__('Regenerating invalidates the previous token. Update every site that embeds the widget. Continue?')) }})">
                    @csrf
                    <x-secondary-button type="submit">
                        {{ $hasApiToken ? __('Regenerate API token') : __('Generate API token') }}
                    </x-secondary-button>
                </form>
                @if (! $apiTokenPlain)
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('profile_embed_token_regenerate_hint') }}</p>
                @else
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('profile_embed_token_persistent_note') }}</p>
                @endif
            </div>

            {{-- ── Form embed snippet ────────────────────────────── --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Embed snippet') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Replace YOUR_FORM_ULID with a form ID from Lead forms (shown in the form editor). Use the fd_live_ token from above — or the placeholder until you generate one.') }}
                </p>

                @if ($apiTokenPlain)
                    <div class="mt-2 rounded-lg border border-emerald-200/80 bg-emerald-50/60 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-200">
                        {{ __('Your real token has been injected into the snippets below. Copy them now.') }}
                    </div>
                @endif

                <div class="mt-4">
                    @include('forms.partials.widget-embed-snippet', [
                        'baseUrl' => $baseUrl,
                        'revealedToken' => $apiTokenPlain,
                        'codeId' => 'flowdesk-widget-settings-embed',
                    ])
                </div>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Widget views and submits are tracked for analytics on each form\'s editor page.') }}
                </p>
            </div>

            {{-- ── Sitewide marketing tracker ────────────────────── --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Sitewide marketing tracker') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('marketing_tracker_settings_blurb') }}</p>

                @if ($apiTokenPlain)
                    <div class="mt-2 rounded-lg border border-emerald-200/80 bg-emerald-50/60 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-200">
                        {{ __('Your real token has been injected into the snippet below. Copy it now.') }}
                    </div>
                @endif

                <div class="mt-4">
                    @include('forms.partials.marketing-tracker-snippet', [
                        'baseUrl' => $baseUrl,
                        'revealedToken' => $apiTokenPlain,
                        'codeId' => 'flowdesk-settings-marketing-tracker',
                    ])
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-6 text-sm text-slate-600 dark:text-slate-400">
                <p><strong>{{ __('CORS') }}:</strong> {{ __('Embed requests must target your tenant hostname so ResolveTenant can load the workspace. For third-party site embeds, allow that origin in your reverse proxy or add Laravel CORS config as needed.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
