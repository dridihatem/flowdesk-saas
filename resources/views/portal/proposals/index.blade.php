<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Quotes') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('portal_my_quotes') }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @can('portal.request_quote')
                    <a href="{{ route('portal.quote-requests.create') }}">
                        <x-primary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                            <i class="fa-solid fa-circle-plus text-sm" aria-hidden="true"></i>
                            {{ __('portal_new_quote_request') }}
                        </x-primary-button>
                    </a>
                @endcan
                <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to portal') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead class="border-b border-slate-200/80 bg-slate-50/80 dark:border-slate-700/80 dark:bg-slate-800/40">
                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-5 py-3 text-start">{{ __('Reference') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Quote title') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Total') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Valid until') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($proposals as $proposal)
                                @php $ic = strtoupper($proposal->currency); @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 font-mono text-slate-800 dark:text-slate-200 text-start">{{ $proposal->number ?? '—' }}</td>
                                    <td class="px-5 py-4 font-medium text-slate-900 dark:text-white text-start">{{ $proposal->name }}</td>
                                    <td class="px-5 py-4 text-start">
                                        <x-flow.badge variant="primary">{{ __('proposal_status.'.$proposal->status->value) }}</x-flow.badge>
                                    </td>
                                    <td class="px-5 py-4 text-end font-medium text-slate-900 dark:text-white"><span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->amount, $ic) }} {{ $ic }}</span></td>
                                    <td class="px-5 py-4 text-end text-slate-600 dark:text-slate-400"><span class="flowdesk-ltr-num tabular-nums">{{ $proposal->valid_until?->format('Y-m-d') ?? '—' }}</span></td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <a
                                                href="{{ route('portal.proposals.show', $proposal) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                title="{{ __('View') }}"
                                            >
                                                <span class="sr-only">{{ __('View') }}</span>
                                                <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                            </a>
                                            <a
                                                href="{{ route('portal.proposals.pdf', $proposal) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:text-rose-400"
                                                title="{{ __('PDF') }}"
                                            >
                                                <span class="sr-only">{{ __('PDF') }}</span>
                                                <i class="fa-regular fa-file-pdf text-sm" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-600 dark:text-slate-400">{{ __('portal_no_quotes') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($proposals->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $proposals->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
