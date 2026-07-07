@php
    use App\Enums\ProposalStatus;

    $ic = strtoupper($proposal->currency);
    $pst = $proposal->status;
    $badgeVariant = match ($pst) {
        ProposalStatus::Accepted => 'success',
        ProposalStatus::Rejected => 'danger',
        ProposalStatus::Expired => 'slate',
        ProposalStatus::Sent => 'primary',
        ProposalStatus::Draft => 'slate',
    };
    $vu = $proposal->valid_until;
    $stillOpen = ! in_array($pst, [ProposalStatus::Accepted, ProposalStatus::Rejected, ProposalStatus::Expired], true);
    $validPast = $vu && $stillOpen && today()->gt($vu);
    $validSoon = $vu && $stillOpen && ! $validPast && $vu->gt(today()) && $vu->lte(today()->copy()->addDays(14));
    $canManage = auth()->user()->hasAnyRole(['company_admin', 'team_member']);
    $isLocked = in_array($pst, [ProposalStatus::Accepted, ProposalStatus::Rejected], true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Quote') }}</p>
                <h2 class="mt-0.5 truncate font-mono text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $proposal->number ?? $proposal->name }}</h2>
                @if ($proposal->number)
                    <p class="mt-0.5 truncate text-sm text-slate-600 dark:text-slate-400">{{ $proposal->name }}</p>
                @endif
            </div>
            @if ($canManage)
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('proposals.pdf', $proposal) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                    >
                        <i class="fa-regular fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                        {{ __('PDF') }}
                    </a>
                    <form method="POST" action="{{ route('proposals.send', $proposal) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-900 shadow-sm transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-100 dark:hover:bg-indigo-900/40"
                        >
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            {{ __('Send') }}
                        </button>
                    </form>
                    @if (! $isLocked)
                        <x-flow.show-action-button :href="route('proposals.edit', $proposal)" variant="edit">{{ __('Edit') }}</x-flow.show-action-button>
                    @endif
                    @if (! $proposal->invoices->isNotEmpty())
                        <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" class="inline" onsubmit="return confirm({{ json_encode(__('Delete this quote?')) }})">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-700 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-950/50"
                            >
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                {{ __('Delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-8xl w-full space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            @if ($validPast)
                <div class="flex items-start gap-3 rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i>
                    <p>{{ __('quote_validity_past', ['date' => $vu->format('Y-m-d')]) }}</p>
                </div>
            @elseif ($validSoon)
                <div class="flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
                    <i class="fa-solid fa-clock mt-0.5 shrink-0" aria-hidden="true"></i>
                    <p>{{ __('quote_validity_soon', ['date' => $vu->format('Y-m-d')]) }}</p>
                </div>
            @endif

            <div class="grid gap-8 xl:grid-cols-3">
                {{-- Main column --}}
                <div class="space-y-6 xl:col-span-2">
                    {{-- Client & meta --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
                        <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700/80 dark:from-slate-800/40 dark:to-slate-900/40">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Client') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $proposal->client?->name ?? '—' }}</p>
                                    @if ($proposal->client?->code)
                                        <p class="mt-0.5 font-mono text-sm text-indigo-600 dark:text-indigo-400">{{ __('Client code') }}: {{ $proposal->client->code }}</p>
                                    @endif
                                    @if ($proposal->client?->email)
                                        <a href="mailto:{{ $proposal->client->email }}" class="mt-1 inline-block text-sm text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">{{ $proposal->client->email }}</a>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <x-flow.badge :variant="$badgeVariant">{{ __('proposal_status.'.$pst->value) }}</x-flow.badge>
                                    @if ($vu)
                                        <p @class([
                                            'mt-2 text-sm tabular-nums',
                                            'font-medium text-rose-600 dark:text-rose-400' => $validPast,
                                            'font-medium text-amber-700 dark:text-amber-300' => $validSoon && ! $validPast,
                                            'text-slate-600 dark:text-slate-400' => ! $validPast && ! $validSoon,
                                        ])>
                                            {{ __('Valid until') }} {{ $vu->format('Y-m-d') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-700/80 dark:bg-slate-800/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Project') }}</p>
                                <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                                    @if ($proposal->project)
                                        <a href="{{ route('projects.show', $proposal->project) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $proposal->project->title }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-700/80 dark:bg-slate-800/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Provider') }}</p>
                                <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $proposal->provider?->name ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-700/80 dark:bg-slate-800/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Sent at') }}</p>
                                <p class="mt-1 text-sm font-medium tabular-nums text-slate-900 dark:text-white">{{ $proposal->sent_at?->format('Y-m-d H:i') ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-700/80 dark:bg-slate-800/30">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Currency') }}</p>
                                <p class="mt-1 text-sm font-semibold uppercase text-slate-900 dark:text-white">{{ $ic }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Line items --}}
                    @if ($proposal->items->isNotEmpty())
                        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
                            <div class="border-b border-slate-200/80 bg-slate-50/90 px-6 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Line items') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Amounts in :currency. Unit and line totals ex. VAT (HT); line TTC allocates document VAT by line share.', ['currency' => $ic]) }}</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full table-fixed text-start text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200/80 bg-white text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700/80 dark:bg-slate-900/60 dark:text-slate-400">
                                            <th class="px-5 py-3 text-start">{{ __('Description') }}</th>
                                            <th class="px-5 py-3 text-end">{{ __('Qty') }}</th>
                                            <th class="px-5 py-3 text-end">{{ __('Unit price (HT)') }}</th>
                                            <th class="px-5 py-3 text-end">{{ __('Line total (HT)') }}</th>
                                            <th class="px-5 py-3 text-end">{{ __('Line total (TTC)') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                        @foreach ($proposal->items as $row)
                                            @php
                                                $lineHt = (int) $row->total_amount;
                                                $lineTtc = $proposal->lineTotalTtcDisplayMinor($lineHt);
                                            @endphp
                                            <tr class="text-slate-800 transition-colors hover:bg-slate-50/80 dark:text-slate-100 dark:hover:bg-slate-800/25">
                                                <td class="px-5 py-3.5 text-start">{{ $row->description }}</td>
                                                <td class="px-5 py-3.5 text-end">
                                                    <span class="flowdesk-ltr-num tabular-nums">{{ $row->quantity }}</span>
                                                </td>
                                                <td class="px-5 py-3.5 text-end">
                                                    <span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row->unit_amount, $ic) }}</span>
                                                </td>
                                                <td class="px-5 py-3.5 text-end">
                                                    <span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor($lineHt, $ic) }}</span>
                                                </td>
                                                <td class="px-5 py-3.5 text-end">
                                                    <span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor($lineTtc, $ic) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($proposal->customer_notes)
                        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                            <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Customer notes') }}</h3>
                            </div>
                            <div class="px-6 py-4">
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $proposal->customer_notes }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($proposal->negotiations->isNotEmpty())
                        @include('proposals.partials.negotiation-timeline', ['proposal' => $proposal, 'currencyOptions' => $currencyOptions])
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                    {{-- Totals --}}
                    <div class="overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/90 to-white shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-slate-900/50">
                        <div class="border-b border-indigo-200/60 px-5 py-4 dark:border-indigo-900/40">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">{{ __('Amount') }}</p>
                        </div>
                        <dl class="space-y-2 px-5 py-4 text-sm">
                            <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300">
                                <dt>{{ __('Subtotal (ex. VAT)') }}</dt>
                                <dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->subtotal_amount, $ic) }}</dd>
                            </div>
                            @if ($proposal->vat_amount > 0)
                                <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300">
                                    <dt>{{ __('VAT') }}</dt>
                                    <dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->vat_amount, $ic) }}</dd>
                                </div>
                            @endif
                            @if ($proposal->fiscal_stamp_amount > 0)
                                <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300">
                                    <dt>{{ __('Fiscal stamp') }}</dt>
                                    <dd class="tabular-nums font-medium">{{ flowdesk_format_minor((int) $proposal->fiscal_stamp_amount, $ic) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-indigo-200/60 pt-3 dark:border-indigo-800/50">
                                <dt class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Total (inc. VAT)') }}</dt>
                                <dd class="text-xl font-bold tabular-nums text-indigo-900 dark:text-indigo-100">
                                    {{ flowdesk_format_minor((int) $proposal->amount, $ic) }}
                                    <span class="text-sm font-normal text-slate-500 dark:text-slate-400">{{ $ic }}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    @if ($canManage)
                        @if ($pst !== ProposalStatus::Accepted && ! $isLocked)
                            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                                <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            <i class="fa-solid fa-handshake text-sm" aria-hidden="true"></i>
                                        </span>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Client acceptance') }}</h3>
                                    </div>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('quote_accept_blurb') }}</p>
                                    <form method="POST" action="{{ route('proposals.accept', $proposal) }}" class="mt-4">
                                        @csrf
                                        <x-primary-button type="submit" class="w-full justify-center">{{ __('Mark as accepted') }}</x-primary-button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if ($pst === ProposalStatus::Accepted)
                            <div class="overflow-hidden rounded-2xl border border-emerald-200/80 bg-emerald-50/40 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <div class="border-b border-emerald-200/60 px-5 py-4 dark:border-emerald-900/40">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-200/80 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                                            <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
                                        </span>
                                        <h3 class="font-semibold text-emerald-950 dark:text-emerald-100">{{ __('Accepted') }}</h3>
                                    </div>
                                </div>
                                <div class="px-5 py-4">
                                    @if ($proposal->invoices->isNotEmpty())
                                        <p class="text-sm text-emerald-900/80 dark:text-emerald-200/90">{{ __('An invoice is linked to this quote.') }}</p>
                                        <a href="{{ route('invoices.show', $proposal->invoices->first()) }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-300/80 bg-white px-4 py-2 text-sm font-medium text-emerald-900 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100 dark:hover:bg-emerald-950/50">
                                            <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                                            {{ __('View invoice') }}
                                        </a>
                                    @else
                                        <p class="text-sm text-emerald-900/80 dark:text-emerald-200/90">{{ __('quote_convert_blurb') }}</p>
                                        <form method="POST" action="{{ route('proposals.invoice', $proposal) }}" class="mt-4">
                                            @csrf
                                            <x-primary-button type="submit" class="w-full justify-center">{{ __('Create invoice from quote') }}</x-primary-button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($pst === ProposalStatus::Rejected)
                            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/50 px-5 py-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                                <p class="flex items-center gap-2 text-sm font-medium text-rose-900 dark:text-rose-100">
                                    <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                                    {{ __('proposal_status.rejected') }}
                                </p>
                            </div>
                        @endif
                    @endif

                    @if ($canManage && $proposal->internal_notes)
                        <div class="overflow-hidden rounded-2xl border border-amber-200/80 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/20">
                            <div class="border-b border-amber-200/60 px-5 py-3 dark:border-amber-900/40">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">{{ __('Internal notes') }}</p>
                            </div>
                            <div class="px-5 py-4">
                                <p class="whitespace-pre-wrap text-sm text-amber-950 dark:text-amber-100">{{ $proposal->internal_notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <x-flow.show-action-button :href="route('proposals.index')" variant="back">{{ __('Back to quotes') }}</x-flow.show-action-button>
        </div>
    </div>
</x-app-layout>
