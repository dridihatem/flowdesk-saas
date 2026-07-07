<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8 space-y-8">
            <x-flow.page-header
                :title="__('workspace_api_connect_title')"
                :description="__('workspace_api_connect_intro')"
            />

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('workspace_api_connect_token_heading') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('workspace_api_connect_token_help') }}</p>

                <div class="mt-4" x-data="{ showToken: false, copied: false, tokenVal: '{{ e($apiTokenPlain ?? '') }}' }">
                    @if ($apiTokenPlain)
                        @if ($revealedToken)
                            <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/60 p-3 dark:border-emerald-800/40 dark:bg-emerald-950/30">
                                <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ __('profile_embed_token_generated_note') }}</p>
                            </div>
                        @endif
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                            <input type="password" readonly x-bind:value="tokenVal" x-show="!showToken" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" autocomplete="off" />
                            <input type="text" readonly x-bind:value="tokenVal" x-show="showToken" x-cloak class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" autocomplete="off" />
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase dark:border-slate-600 dark:bg-slate-800" x-on:click="showToken = !showToken">
                                    <span x-show="showToken" x-cloak>{{ __('widget_embed_hide_token') }}</span>
                                    <span x-show="!showToken">{{ __('widget_embed_show_token') }}</span>
                                </button>
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase dark:border-slate-600 dark:bg-slate-800" x-on:click="navigator.clipboard.writeText(tokenVal); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('widget_embed_token_copied') }}</span>
                                </button>
                            </div>
                        </div>
                    @elseif ($hasApiToken)
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ __('profile_embed_token_legacy_note') }}</p>
                        @if ($apiTokenHint)
                            <p class="mt-2 text-xs text-slate-500">{{ __('Token hint:') }} …{{ $apiTokenHint }}</p>
                        @endif
                    @else
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ __('workspace_api_connect_no_token') }}</p>
                    @endif

                    <form method="POST" action="{{ route('settings.api-connect.regenerate-token') }}" class="mt-4" onsubmit="return confirm({{ json_encode(__('Regenerate API token? Existing integrations will stop working until you update them.')) }})">
                        @csrf
                        <x-primary-button type="submit">{{ __('Generate API token') }}</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50 prose prose-slate max-w-none dark:prose-invert">
                <h3 class="!mt-0 text-lg font-semibold">{{ __('workspace_api_docs_heading') }}</h3>
                <p>{{ __('workspace_api_docs_overview') }}</p>

                <h4>{{ __('workspace_api_docs_base_url') }}</h4>
                <pre class="rounded-lg bg-slate-900 p-4 text-sm text-slate-100 overflow-x-auto"><code>{{ $workspaceApiBase }}</code></pre>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('workspace_api_docs_tenant_note', ['subdomain' => $subdomain ?? '—']) }}</p>

                <h4>{{ __('workspace_api_docs_auth') }}</h4>
                <pre class="rounded-lg bg-slate-900 p-4 text-sm text-slate-100 overflow-x-auto"><code>Authorization: Bearer fd_live_…
Content-Type: application/json
Accept: application/json</code></pre>

                <h4>{{ __('workspace_api_docs_endpoints') }}</h4>
                <div class="overflow-x-auto not-prose">
                    <table class="min-w-full text-sm text-start border border-slate-200 dark:border-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-3 py-2 text-start">{{ __('Method') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('Path') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr><td class="px-3 py-2 font-mono text-start">GET</td><td class="px-3 py-2 font-mono text-start">/</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_me') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">GET</td><td class="px-3 py-2 font-mono text-start">/clients</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_clients_list') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">POST</td><td class="px-3 py-2 font-mono text-start">/clients</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_clients_create') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">GET</td><td class="px-3 py-2 font-mono text-start">/clients/{id}</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_clients_show') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">GET</td><td class="px-3 py-2 font-mono text-start">/projects</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_projects_list') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">POST</td><td class="px-3 py-2 font-mono text-start">/projects</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_projects_create') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">GET</td><td class="px-3 py-2 font-mono text-start">/invoices</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_invoices_list') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">POST</td><td class="px-3 py-2 font-mono text-start">/invoices</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_invoices_create') }}</td></tr>
                            <tr><td class="px-3 py-2 font-mono text-start">POST</td><td class="px-3 py-2 font-mono text-start">/import</td><td class="px-3 py-2 text-start">{{ __('workspace_api_ep_import') }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4>{{ __('workspace_api_docs_example_client') }}</h4>
                <pre class="rounded-lg bg-slate-900 p-4 text-sm text-slate-100 overflow-x-auto"><code>curl -X POST "{{ $workspaceApiBase }}/clients" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Acme Corp","email":"billing@acme.test","phone":"+21612345678"}'</code></pre>

                <h4>{{ __('workspace_api_docs_example_invoice') }}</h4>
                <pre class="rounded-lg bg-slate-900 p-4 text-sm text-slate-100 overflow-x-auto"><code>curl -X POST "{{ $workspaceApiBase }}/invoices" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "01H…",
    "currency": "EUR",
    "due_date": "2026-07-01",
    "items": [
      {"description": "Consulting", "quantity": 1, "unit_amount": 150000}
    ]
  }'</code></pre>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('workspace_api_docs_amounts_note') }}</p>

                <h4>{{ __('workspace_api_docs_example_import') }}</h4>
                <pre class="rounded-lg bg-slate-900 p-4 text-sm text-slate-100 overflow-x-auto"><code>curl -X POST "{{ $workspaceApiBase }}/import" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "clients": [{"ref": "c1", "name": "Client A", "email": "a@test.com"}],
    "projects": [{"title": "Website", "client_ref": "c1"}]
  }'</code></pre>
            </div>
        </div>
    </div>
</x-app-layout>
