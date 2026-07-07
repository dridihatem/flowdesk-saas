@php
    use App\Enums\ProposalStatus;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ __('Quotes') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Quotes and estimates with line items — send to clients and convert to invoices when accepted.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                <div class="mb-6 flex justify-end">
                    <a href="{{ route('proposals.create') }}">
                        <x-primary-button type="button" class="!normal-case inline-flex items-center gap-2">
                            <i class="fa-solid fa-file-circle-plus text-sm" aria-hidden="true"></i>
                            {{ __('New quote') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/40 dark:ring-white/[0.06]">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50/95 to-white px-5 py-4 dark:border-slate-700/80 dark:from-slate-800/50 dark:to-slate-900/40">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('All quotes') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Validity and status help you follow up at a glance.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-400">
                                <th class="px-5 py-3.5 text-start">{{ __('Reference') }}</th>
                                <th class="px-5 py-3.5 text-start">{{ __('Title') }}</th>
                                <th class="px-5 py-3.5 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3.5 text-end">{{ __('Amount') }}</th>
                                <th class="px-5 py-3.5 text-start">{{ __('Project') }}</th>
                                <th class="px-5 py-3.5 text-start">{{ __('Provider') }}</th>
                                <th class="px-5 py-3.5 text-end">{{ __('Valid until') }}</th>
                                <th class="px-5 py-3.5 w-24 text-end"><span class="sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @forelse ($proposals as $proposal)
                                @php
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
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/90 dark:hover:bg-slate-800/25">
                                    <td class="px-5 py-3.5 text-start font-mono text-xs text-slate-600 dark:text-slate-400">{{ $proposal->number ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-start">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ $proposal->name }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-start">
                                        <x-flow.badge :variant="$badgeVariant">{{ __('proposal_status.'.$pst->value) }}</x-flow.badge>
                                    </td>
                                    <td class="px-5 py-3.5 text-end font-medium text-slate-900 dark:text-white">
                                        <span class="flowdesk-ltr-num tabular-nums">
                                            {{ flowdesk_format_minor((int) $proposal->amount, $proposal->currency) }}
                                            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $proposal->currency }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-start text-slate-700 dark:text-slate-300">{{ $proposal->project?->title ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-start text-slate-600 dark:text-slate-400">{{ $proposal->provider?->name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-end">
                                        @if ($vu)
                                            <span @class([
                                                'flowdesk-ltr-num inline-flex flex-wrap items-center gap-1.5 text-sm font-medium tabular-nums',
                                                'text-rose-600 dark:text-rose-400' => $validPast,
                                                'text-amber-600/90 dark:text-amber-400/90' => $validSoon,
                                                'text-slate-600 dark:text-slate-400' => ! $validPast && ! $validSoon,
                                            ])>
                                                @if ($validPast)
                                                    <i class="fa-solid fa-circle-exclamation text-xs" aria-hidden="true"></i>
                                                    {{ $vu->format('M j, Y') }}
                                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800 dark:bg-rose-950/60 dark:text-rose-200">{{ __('Expired') }}</span>
                                                @elseif ($validSoon)
                                                    {{ $vu->format('M j, Y') }}
                                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-amber-700/80 dark:text-amber-400">{{ __('Ending soon') }}</span>
                                                @else
                                                    {{ $vu->format('M j, Y') }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-end">
                                        <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                            <a
                                                href="{{ route('proposals.show', $proposal) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                title="{{ __('View') }}"
                                            >
                                                <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                            </a>
                                            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                                <a
                                                    href="{{ route('proposals.edit', $proposal) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                    title="{{ __('Edit') }}"
                                                >
                                                    <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-16 text-center">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('No quotes yet.') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Send polished estimates and convert them to invoices when accepted.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $proposals->links() }}</div>
        </div>
    </div>
</x-app-layout>
