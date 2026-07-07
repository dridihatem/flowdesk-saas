<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Client portal') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-indigo-50/80 via-white to-white p-6 shadow-sm dark:border-slate-700/80 dark:from-indigo-950/30 dark:via-slate-900/50 dark:to-slate-900/40">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('portal_welcome', ['name' => $client->name]) }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_dashboard_intro') }}</p>
            </div>

            @if ($sharedNotes->isNotEmpty())
                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('portal_shared_notes_heading') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_shared_notes_intro') }}</p>
                    <ul class="mt-4 space-y-3">
                        @foreach ($sharedNotes as $note)
                            <li class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/80">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $note->note_type?->label() }}</span>
                                    <span>{{ $note->authorLabel($client->company?->name) }}</span>
                                    <time datetime="{{ $note->noted_on->toDateString() }}">{{ $note->noted_on->format('Y-m-d') }}@if ($note->start_time) · {{ \Illuminate\Support\Str::substr((string) $note->start_time, 0, 5) }}@endif</time>
                                </div>
                                @if ($note->title)
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $note->title }}</p>
                                @endif
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $note->body }}</p>
                                @if ($note->meeting_url)
                                    <a href="{{ $note->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('client_meeting_join_button') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($canViewInvoices || $canViewProposals)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($canViewInvoices)
                        <a href="{{ route('portal.payments.index') }}" class="block transition hover:scale-[1.01]">
                            <x-flow.stat-card :label="__('portal_stat_pending_payment_invoices')" variant="amber">
                                <div class="flex flex-col gap-1">
                                    <span>{{ number_format($pendingPaymentInvoices) }}</span>
                                    <span class="text-sm font-semibold text-amber-800/90 dark:text-amber-200/90">
                                        {{ flowdesk_format_minor((int) $pendingPaymentOutstandingMinor, $currency) }} {{ $currency }}
                                    </span>
                                </div>
                            </x-flow.stat-card>
                        </a>
                    @endif
                    @if ($canViewProposals)
                        <a href="{{ route('portal.proposals.index') }}" class="block transition hover:scale-[1.01]">
                            <x-flow.stat-card :label="__('portal_stat_pending_acceptance')" variant="indigo">
                                {{ number_format($pendingAcceptanceProposals) }}
                            </x-flow.stat-card>
                        </a>
                    @endif
                    @if ($canViewInvoices)
                        <a href="{{ route('portal.payments.index') }}" class="block transition hover:scale-[1.01]">
                            <x-flow.stat-card :label="__('portal_stat_total_payments')" variant="emerald">
                                <div class="flex flex-col gap-1">
                                    <span>{{ flowdesk_format_minor((int) $totalPaymentsMinor, $currency) }}</span>
                                    <span class="text-sm font-semibold text-emerald-800/90 dark:text-emerald-200/90">{{ $currency }}</span>
                                </div>
                            </x-flow.stat-card>
                        </a>
                    @endif
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @can('portal.view_projects')
                    <a href="{{ route('portal.projects.index') }}" class="group rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/40 dark:hover:border-indigo-800">
                        <i class="fa-solid fa-folder-open text-indigo-600 dark:text-indigo-400" aria-hidden="true"></i>
                        <p class="mt-3 font-semibold text-slate-900 dark:text-white">{{ __('My projects') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_card_projects') }}</p>
                    </a>
                @endcan
                @can('portal.request_quote')
                    <a href="{{ route('portal.quote-requests.create') }}" class="group rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/40 dark:hover:border-indigo-800">
                        <i class="fa-solid fa-file-circle-plus text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        <p class="mt-3 font-semibold text-slate-900 dark:text-white">{{ __('portal_quote_requests') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_card_quote_request') }}</p>
                    </a>
                @endcan
                @can('portal.view_proposals')
                    <a href="{{ route('portal.proposals.index') }}" class="group rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/40 dark:hover:border-indigo-800">
                        <i class="fa-solid fa-file-contract text-violet-600 dark:text-violet-400" aria-hidden="true"></i>
                        <p class="mt-3 font-semibold text-slate-900 dark:text-white">{{ __('Quotes') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_card_quotes') }}</p>
                    </a>
                @endcan
                @if (auth()->user()?->can('portal.view_invoices') || auth()->user()?->can('portal.view_payments'))
                    <a href="{{ route('portal.payments.index') }}" class="group rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/40 dark:hover:border-indigo-800">
                        <i class="fa-solid fa-file-invoice-dollar text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                        <p class="mt-3 font-semibold text-slate-900 dark:text-white">{{ __('Invoices') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_card_invoices') }}</p>
                    </a>
                @endif
                <a href="{{ route('chat.index') }}" class="group rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-900/40 dark:hover:border-indigo-800">
                    <i class="fa-solid fa-comments text-sky-600 dark:text-sky-400" aria-hidden="true"></i>
                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">{{ __('Messages') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_card_messages') }}</p>
                </a>
            </div>

            @if (! empty($flowdeskCalendarNav))
                <x-calendar-preview-panel :preview="$flowdeskCalendarNav" />
            @endif
        </div>
    </div>
</x-app-layout>
