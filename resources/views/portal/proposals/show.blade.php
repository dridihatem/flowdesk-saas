@php
    use App\Enums\ProposalStatus;
    $ic = strtoupper($proposal->currency);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Quote') }}</p>
                <h2 class="mt-0.5 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $proposal->number ?? $proposal->name }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('portal.proposals.pdf', $proposal) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    <i class="fa-regular fa-file-pdf text-rose-600" aria-hidden="true"></i>
                    {{ __('PDF') }}
                </a>
                <a href="{{ route('portal.proposals.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300">{{ __('Back to quotes') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first() }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-indigo-50/80 to-white px-6 py-5 dark:border-slate-700/80 dark:from-indigo-950/30 dark:to-slate-900/40">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">{{ $proposal->name }}</p>
                            @if ($proposal->project)
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Project') }}: {{ $proposal->project->title }}</p>
                            @endif
                        </div>
                        <div class="text-end">
                            <x-flow.badge variant="primary">{{ __('proposal_status.'.$proposal->status->value) }}</x-flow.badge>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $proposal->amount, $ic) }} <span class="text-sm font-normal text-slate-500">{{ $ic }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('Valid until') }}</dt>
                            <dd class="mt-0.5 tabular-nums">{{ $proposal->valid_until?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">{{ __('Sent at') }}</dt>
                            <dd class="mt-0.5 tabular-nums">{{ $proposal->sent_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($proposal->items->isNotEmpty())
                        <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                            <table class="min-w-full table-fixed text-start text-sm">
                                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 text-start">{{ __('Description') }}</th>
                                        <th class="px-4 py-3 text-end">{{ __('Qty') }}</th>
                                        <th class="px-4 py-3 text-end">{{ __('Unit price (HT)') }}</th>
                                        <th class="px-4 py-3 text-end">{{ __('Line total (HT)') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                    @foreach ($proposal->items as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-800 dark:text-slate-200 text-start">{{ $row->description }}</td>
                                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ $row->quantity }}</span></td>
                                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row->unit_amount, $ic) }}</span></td>
                                            <td class="px-4 py-3 text-end font-medium"><span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $row->total_amount, $ic) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <dl class="mt-6 ms-auto max-w-sm space-y-1 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Subtotal (ex. VAT)') }}</dt><dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->subtotal_amount, $ic) }}</dd></div>
                            @if ($proposal->vat_amount > 0)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('VAT') }}</dt><dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->vat_amount, $ic) }}</dd></div>
                            @endif
                            @if ($proposal->fiscal_stamp_amount > 0)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Fiscal stamp') }}</dt><dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->fiscal_stamp_amount, $ic) }}</dd></div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 text-base font-semibold dark:border-slate-700"><dt>{{ __('Total (inc. VAT)') }}</dt><dd class="tabular-nums">{{ flowdesk_format_minor((int) $proposal->amount, $ic) }}</dd></div>
                        </dl>
                    @endif

                    @if ($proposal->customer_notes)
                        <div class="mt-6 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800/40">
                            <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Customer notes') }}</p>
                            <p class="mt-2 whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ $proposal->customer_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if (in_array($proposal->status, [ProposalStatus::Sent, ProposalStatus::Expired], true))
                <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-6 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Accept quote') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_accept_quote_help') }}</p>
                    <form method="POST" action="{{ route('portal.proposals.accept', $proposal) }}" class="mt-4" onsubmit="return confirm(@js(__('portal_accept_quote_confirm')))">
                        @csrf
                        <x-primary-button type="submit">{{ __('Accept quote') }}</x-primary-button>
                    </form>
                </div>
            @elseif ($proposal->status === ProposalStatus::Accepted)
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                    <p class="font-semibold text-emerald-900 dark:text-emerald-100">{{ __('portal_quote_accepted_banner') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
