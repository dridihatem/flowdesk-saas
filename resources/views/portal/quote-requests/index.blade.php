<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Client portal') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('portal_quote_requests') }}</h2>
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
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="border-b border-slate-200/80 bg-slate-50/80 px-5 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('portal_quote_requests_intro') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:text-slate-400">
                                <th class="px-5 py-3 text-start">{{ __('Subject') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Created') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($requests as $requestRow)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 font-medium text-slate-900 dark:text-white text-start">{{ $requestRow->subject }}</td>
                                    <td class="px-5 py-4 text-start">
                                        <x-flow.badge variant="primary">{{ __('inquiry_status.'.$requestRow->status->value) }}</x-flow.badge>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $requestRow->created_at?->format('Y-m-d H:i') }}</span></td>
                                    <td class="px-5 py-4 text-end">
                                        <a
                                            href="{{ route('portal.quote-requests.show', $requestRow) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('View') }}"
                                        >
                                            <span class="sr-only">{{ __('View') }}</span>
                                            <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-slate-600 dark:text-slate-400">
                                        <p>{{ __('portal_no_quote_requests') }}</p>
                                        @can('portal.request_quote')
                                            <a href="{{ route('portal.quote-requests.create') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                <i class="fa-solid fa-circle-plus text-xs" aria-hidden="true"></i>
                                                {{ __('portal_new_quote_request') }}
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($requests->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $requests->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
