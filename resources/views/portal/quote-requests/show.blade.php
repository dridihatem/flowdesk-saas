<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('portal_quote_requests') }}</p>
                <h2 class="mt-0.5 font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $inquiry->subject }}</h2>
            </div>
            <a href="{{ route('portal.quote-requests.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to quote requests') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <div class="border-b border-slate-200/80 bg-slate-50/80 px-6 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-flow.badge variant="primary">{{ __('inquiry_status.'.$inquiry->status->value) }}</x-flow.badge>
                        <p class="text-sm tabular-nums text-slate-500 dark:text-slate-400">{{ $inquiry->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
                <div class="space-y-5 px-6 py-6">
                    @if ($inquiry->message)
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('portal_quote_request_details') }}</h3>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{{ $inquiry->message }}</p>
                        </div>
                    @endif
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        @if ($inquiry->contact_phone)
                            <div>
                                <dt class="text-slate-500">{{ __('Phone') }}</dt>
                                <dd class="mt-0.5 font-medium">{{ $inquiry->contact_phone }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-slate-500">{{ __('Contact email') }}</dt>
                            <dd class="mt-0.5 font-medium">{{ $inquiry->contact_email ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if ($inquiry->status === \App\Enums\InquiryStatus::Closed && $inquiry->project_id)
                        <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
                            {{ __('portal_quote_request_linked_project') }}
                        </div>
                    @elseif ($inquiry->status === \App\Enums\InquiryStatus::New)
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('portal_quote_request_pending_hint') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
