<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Tickets') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-end">
                <a href="{{ route('tickets.create') }}">
                    <x-primary-button type="button">{{ __('New ticket') }}</x-primary-button>
                </a>
            </div>

            <div class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Subject') }}</th>
                            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                <th class="px-4 py-3 text-start">{{ __('Related') }}</th>
                            @endif
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                <th class="px-4 py-3 text-start">{{ __('Opened by') }}</th>
                            @endif
                            <th class="px-4 py-3 text-start">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                        @forelse ($tickets as $ticket)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-start">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $ticket->title }}</a>
                                </td>
                                @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">
                                        @if ($ticket->client_id)
                                            {{ __('Client') }}: {{ $ticket->client?->name ?? '—' }}
                                        @elseif ($ticket->provider_id)
                                            {{ __('Business provider') }}: {{ $ticket->provider?->name ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-start">
                                    <x-flow.badge variant="primary">{{ $ticket->status->label() }}</x-flow.badge>
                                </td>
                                @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">{{ $ticket->user?->name ?? '—' }}</td>
                                @endif
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $ticket->created_at->format('Y-m-d H:i') }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasAnyRole(['company_admin', 'team_member']) ? 5 : 3 }}" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No tickets yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            @if ($tickets->hasPages())
                <div class="mt-6">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
