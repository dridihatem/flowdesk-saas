@php
    $chartPayload = [
        'labels' => $series['labels'],
        'views' => $series['views'],
        'submits' => $series['submits'],
        'pageviews' => $pageviewSeries ?? [],
    ];
    $isAdmin = auth()->user()?->hasRole('company_admin');
    $m = is_array($marketing) ? $marketing : [];
    $trackerBaseUrl = rtrim(request()->getSchemeAndHttpHost().request()->getBaseUrl(), '/');
    $trackerToken = session('flowdesk_company_api_token_plain') ?: auth()->user()?->company?->apiTokenPlain();
@endphp

<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Marketing hub')"
                :description="__('Website & widget traffic')"
            />

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <p class="mt-6 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Track embed performance on your site and connect analytics or ads tools. Full site traffic lives in Google Analytics or similar once you install their tags.') }}
            </p>

            <div class="mt-8 rounded-2xl border border-indigo-200/80 bg-indigo-50/40 p-6 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Sitewide marketing tracker (JavaScript)') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('marketing_tracker_hub_help') }}</p>
                <div class="mt-4">
                    @include('forms.partials.marketing-tracker-snippet', [
                        'baseUrl' => $trackerBaseUrl,
                        'revealedToken' => $trackerToken,
                        'codeId' => 'flowdesk-hub-marketing-tracker',
                    ])
                </div>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                    <a href="{{ route('settings.widget-embed') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Widget embed') }}</a>
                    — {{ __('marketing_tracker_token_help') }}
                </p>
            </div>

            @if (count($hintList ?? []) > 0)
                <div class="mt-8 rounded-2xl border border-amber-200/80 bg-amber-50/50 p-6 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('SEO checklist & suggestions') }}</h3>
                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        @foreach ($hintList as $hint)
                            <li>{{ $hint }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                id="flowdesk-marketing-hub-root"
                class="mt-8"
                data-chart="{{ json_encode($chartPayload) }}"
                data-label-views="{{ __('Widget views (:days d)', ['days' => $days]) }}"
                data-label-submits="{{ __('Widget submissions (:days d)', ['days' => $days]) }}"
                data-label-pageviews="{{ __('Site page views (:days d)', ['days' => $days]) }}"
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-flow.stat-card :label="__('Site page views (:days d)', ['days' => $days])" variant="cyan">
                        {{ number_format($sitePageviews ?? 0) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Widget views (:days d)', ['days' => $days])" variant="indigo">
                        {{ number_format($totals['views']) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Widget submissions (:days d)', ['days' => $days])" variant="emerald">
                        {{ number_format($totals['submits']) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Widget conversion')" variant="amber">
                        @if (($totals['rate'] ?? null) !== null)
                            {{ number_format((float) $totals['rate'], 2) }}%
                        @else
                            —
                        @endif
                    </x-flow.stat-card>
                </div>

                <div class="mt-8 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/50">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Traffic per day (site + lead widget)') }}</h3>
                    <div class="mt-4 h-72 w-full">
                        <canvas id="chart-widget-traffic"></canvas>
                    </div>
                </div>
            </div>

            <div class="mt-10 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Lead form performance (:days d)', ['days' => $days]) }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead>
                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="py-3 pr-4 text-start">{{ __('Name') }}</th>
                                <th class="py-3 pr-4 text-start">{{ __('Views') }}</th>
                                <th class="py-3 pr-4 text-start">{{ __('Submissions') }}</th>
                                <th class="py-3 text-start">{{ __('Conversion %') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($byForm as $row)
                                <tr>
                                    <td class="py-3 pr-4 text-start">
                                        <a href="{{ route('forms.edit', $row['form_id']) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            {{ $row['name'] }}
                                        </a>
                                    </td>
                                    <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['views']) }}</span></td>
                                    <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['submits']) }}</span></td>
                                    <td class="py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">
                                        {{ $row['rate'] !== null ? number_format($row['rate'], 2).'%' : '—' }}
                                    </span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                        {{ __('No lead forms yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($totals['views'] === 0 && $totals['submits'] === 0 && count($byForm) > 0)
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('No widget activity in this period. Publish a form and embed the widget on your site.') }}</p>
                @endif
            </div>

            <div class="mt-10 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Top pages (lead widget) (:days d)', ['days' => $days]) }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Paths when the embed script sends page URL and path (latest widget build).') }}</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead>
                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="py-3 pr-4 text-start">{{ __('Path') }}</th>
                                <th class="py-3 pr-4 text-start">{{ __('Views') }}</th>
                                <th class="py-3 pr-4 text-start">{{ __('Submissions') }}</th>
                                <th class="py-3 text-start">{{ __('Page title (sample)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($widgetTopPaths ?? [] as $row)
                                <tr>
                                    <td class="py-3 pr-4 font-mono text-xs text-slate-800 dark:text-slate-200 text-start">{{ $row['path'] }}</td>
                                    <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['views']) }}</span></td>
                                    <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['submits']) }}</span></td>
                                    <td class="py-3 text-slate-600 dark:text-slate-400 text-start">{{ $row['title'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                        {{ __('No lead-widget page data yet. Copy the latest embed snippet from Widget embed so page paths are recorded.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-10 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Top pages (sitewide tracker) (:days d)', ['days' => $days]) }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Paths from the marketing tracker script only.') }}</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead>
                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="py-3 pr-4 text-start">{{ __('Path') }}</th>
                                <th class="py-3 pr-4 text-start">{{ __('Views') }}</th>
                                <th class="py-3 text-start">{{ __('Page title (sample)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($topPaths ?? [] as $row)
                                <tr>
                                    <td class="py-3 pr-4 font-mono text-xs text-slate-800 dark:text-slate-200 text-start">{{ $row['path'] }}</td>
                                    <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['count']) }}</span></td>
                                    <td class="py-3 text-slate-600 dark:text-slate-400 text-start">{{ $row['title'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                        {{ __('No sitewide page views yet. Add the tracker script to your website.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Marketing tools & integrations') }}</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('Google Analytics 4') }}</h4>
                            <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer" class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Open Analytics') }}</a>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Save your Measurement ID below, then add Google’s gtag or GTM on your website. Reports and acquisition data stay in Google Analytics.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('Google Tag Manager') }}</h4>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Container ID for firing GA, Ads, and other tags from one place.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('Meta Pixel') }}</h4>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('For Facebook / Instagram ads and remarketing. Install the base code on your site; store the Pixel ID here for your records.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('Zapier & Make') }}</h4>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Connect Flowqil to Mailchimp, HubSpot, Slack, and thousands of other tools when outbound webhooks and the public API are available.') }}</p>
                    </div>
                </div>
            </div>

            @if ($isAdmin)
                <form method="POST" action="{{ route('marketing.hub.update') }}" class="mt-10 space-y-6 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50">
                    @csrf
                    @method('PATCH')
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Marketing details') }}</h3>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="website_url" :value="__('Website URL')" />
                            <x-text-input id="website_url" name="website_url" type="url" class="mt-1 block w-full" :value="old('website_url', $m['website_url'] ?? '')" placeholder="https://example.com" />
                            <x-input-error :messages="$errors->get('website_url')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="google_analytics_measurement_id" :value="__('Measurement ID (GA4)')" />
                            <x-text-input id="google_analytics_measurement_id" name="google_analytics_measurement_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('google_analytics_measurement_id', $m['google_analytics_measurement_id'] ?? '')" placeholder="G-XXXXXXXXXX" />
                            <x-input-error :messages="$errors->get('google_analytics_measurement_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="google_tag_manager_id" :value="__('GTM container ID')" />
                            <x-text-input id="google_tag_manager_id" name="google_tag_manager_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('google_tag_manager_id', $m['google_tag_manager_id'] ?? '')" placeholder="GTM-XXXXXXX" />
                            <x-input-error :messages="$errors->get('google_tag_manager_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="meta_pixel_id" :value="__('Pixel ID')" />
                            <x-text-input id="meta_pixel_id" name="meta_pixel_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('meta_pixel_id', $m['meta_pixel_id'] ?? '')" />
                            <x-input-error :messages="$errors->get('meta_pixel_id')" class="mt-2" />
                        </div>
                    </div>
                    <x-primary-button type="submit">{{ __('Save marketing settings') }}</x-primary-button>
                </form>
            @else
                <p class="mt-10 text-sm text-slate-500 dark:text-slate-400">{{ __('Marketing settings can only be updated by company admins.') }}</p>
            @endif

            {{-- Single-quoted x-data so @json() strings (double quotes) do not terminate the HTML attribute --}}
            <div
                class="mt-10 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50"
                x-data='{
                    context: "",
                    result: "",
                    error: "",
                    loading: false,
                    async suggest() {
                        this.loading = true;
                        this.error = "";
                        this.result = "";
                        const ctxEl = document.getElementById("seo_context");
                        const contextText = (ctxEl && ctxEl.value ? ctxEl.value : this.context || "").trim();
                        this.context = contextText;
                        if (!contextText) {
                            this.error = @json(__('seo_context_required'));
                            this.loading = false;
                            return;
                        }
                        try {
                            const res = await fetch(@json(route("assistant.suggest")), {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "Accept": "application/json",
                                    "X-CSRF-TOKEN": @json(csrf_token()),
                                    "X-Requested-With": "XMLHttpRequest"
                                },
                                body: JSON.stringify({ mode: "seo", context: contextText })
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                throw new Error(data.message || @json(__("Something went wrong.")));
                            }
                            this.result = data.suggestion || "";
                        } catch (e) {
                            this.error = e.message || @json(__("Something went wrong."));
                        } finally {
                            this.loading = false;
                        }
                    }
                }'
            >
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('SEO suggestions (AI)') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Describe your page or site: URL, target keywords, country/language, and current title or meta description if you have them. Suggestions are advisory and do not replace Search Console or an SEO audit.') }}</p>
                <div class="mt-4">
                    <x-input-label for="seo_context" :value="__('Context for SEO assistant')" />
                    <x-ai-voice-wrap target-id="seo_context" class="mt-1">
                        <textarea
                            id="seo_context"
                            rows="6"
                            class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            placeholder="https://…"
                        ></textarea>
                    </x-ai-voice-wrap>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-primary-button type="button" @click="suggest()" x-bind:disabled="loading">
                        <span x-show="!loading">{{ __('Get SEO suggestions') }}</span>
                        <span x-show="loading" x-cloak>{{ __('Generating…') }}</span>
                    </x-primary-button>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Uses AI credits from your plan.') }}</span>
                </div>
                <p x-show="error" x-text="error" class="mt-4 text-sm text-red-600 dark:text-red-400" x-cloak></p>
                <div x-show="result" x-cloak class="mt-6">
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Suggestion') }}</h4>
                    <pre class="mt-2 max-h-[28rem] overflow-auto whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-sm text-slate-800 dark:bg-slate-950 dark:text-slate-200" x-text="result"></pre>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/marketing-hub.js')
    @endpush
</x-app-layout>
