@php
    $m = is_array($profileMarketing ?? []) ? $profileMarketing : [];
    $website = trim((string) ($m['website_url'] ?? ''));
    $ga = trim((string) ($m['google_analytics_measurement_id'] ?? ''));
    $gtm = trim((string) ($m['google_tag_manager_id'] ?? ''));
    $pixel = trim((string) ($m['meta_pixel_id'] ?? ''));
@endphp

@if (($flowdeskPlanGates['marketing_hub'] ?? true) || ($flowdeskPlanGates['widgets'] ?? true))
    <div class="space-y-6">
        @if ($flowdeskPlanGates['widgets'] ?? true)
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-5 dark:border-slate-600/80 dark:bg-slate-900/40">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Company API token (embed)') }}</h4>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('profile_embed_token_blurb') }}</p>

            <div class="mt-4" x-data="{ showToken: false, copied: false, tokenVal: @js($profileApiTokenPlain ?? '') }">
                @if (! empty($profileApiTokenPlain))
                    @if (! empty($profileRevealedToken))
                        <div class="mb-3 rounded-lg border border-emerald-200/80 bg-emerald-50/60 p-3 dark:border-emerald-800/40 dark:bg-emerald-950/30">
                            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ __('profile_embed_token_generated_note') }}</p>
                        </div>
                    @endif
                    <label for="flowdesk-profile-embed-api-token-pw" class="sr-only">{{ __('Company API token (embed)') }}</label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <div class="relative min-w-0 flex-1">
                            <input
                                id="flowdesk-profile-embed-api-token-pw"
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
                                x-on:click="if (tokenVal) { navigator.clipboard.writeText(tokenVal); copied = true; clearTimeout(window._flowdeskProfileEmbedCopyT); window._flowdeskProfileEmbedCopyT = setTimeout(() => { copied = false }, 2000); }"
                            >
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied" x-cloak>{{ __('widget_embed_token_copied') }}</span>
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('profile_embed_token_persistent_note') }}
                    </p>
                @elseif ($profileHasApiToken ?? false)
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ __('A token is configured.') }}
                        @if (! empty($profileApiTokenHint))
                            <span class="ms-1 font-mono text-slate-800 dark:text-slate-200">{{ __('Ends with') }} …{{ $profileApiTokenHint }}</span>
                        @endif
                    </p>
                    <p class="mt-2 text-sm text-amber-800 dark:text-amber-200/90">
                        {{ __('profile_embed_token_legacy_note') }}
                    </p>
                @else
                    <p class="text-sm text-amber-800 dark:text-amber-200/90">{{ __('No company API token yet. Generate one to use the scripts below.') }}</p>
                    <label for="flowdesk-profile-embed-api-token-empty" class="sr-only">{{ __('Company API token (embed)') }}</label>
                    <input
                        id="flowdesk-profile-embed-api-token-empty"
                        type="text"
                        readonly
                        placeholder="{{ __('widget_embed_no_token_placeholder') }}"
                        value=""
                        class="mt-2 block w-full rounded-lg border border-dashed border-slate-300 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-400"
                    />
                @endif
            </div>

            <form method="POST" action="{{ route('profile.embed-token.regenerate') }}" class="mt-6" onsubmit="return confirm({{ json_encode(__('Regenerating invalidates the previous token. Update every site that uses it. Continue?')) }})">
                @csrf
                <x-secondary-button type="submit">{{ __('Generate new API token') }}</x-secondary-button>
            </form>
            @if (empty($profileApiTokenPlain))
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('profile_embed_token_regenerate_hint') }}</p>
            @endif
        </div>
        @endif

        @if ($flowdeskPlanGates['marketing_hub'] ?? true)
        <div class="rounded-xl border border-indigo-200/70 bg-indigo-50/40 p-5 dark:border-indigo-900/40 dark:bg-indigo-950/20">
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Sitewide header script (traffic & page paths)') }}</h4>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('profile_header_tracker_blurb') }}</p>
            <div class="mt-4">
                @include('forms.partials.marketing-tracker-snippet', [
                    'baseUrl' => $profileEmbedBaseUrl,
                    'revealedToken' => $profileApiTokenPlain ?? null,
                    'codeId' => 'flowdesk-profile-marketing-tracker',
                ])
            </div>
        </div>
        @endif

        @if ($flowdeskPlanGates['marketing_hub'] ?? true)
        <div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Saved marketing details') }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Shown for reference; company admins can edit them in the Marketing hub.') }}</p>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Website URL') }}</dt>
                    <dd class="mt-0.5 break-all text-slate-800 dark:text-slate-200">{{ $website !== '' ? $website : '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Measurement ID (GA4)') }}</dt>
                    <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $ga !== '' ? $ga : '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('GTM container ID') }}</dt>
                    <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $gtm !== '' ? $gtm : '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Meta Pixel ID') }}</dt>
                    <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $pixel !== '' ? $pixel : '—' }}</dd>
                </div>
            </dl>
        </div>
        @endif

    </div>
@endif
