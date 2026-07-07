<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Client signup requests') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->has('approve'))
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first('approve') }}</div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Back to clients') }}</a>
            </div>

            <div class="flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/60">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Pending') }}</h3>
                </div>
                @forelse ($pending as $row)
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 last:border-0 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 text-sm">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $row->name }}</p>
                            <p class="text-slate-600 dark:text-slate-300">{{ $row->email }}</p>
                            @if ($row->phone)
                                <p class="text-slate-500 dark:text-slate-400">{{ $row->phone }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Requested by :name', ['name' => $row->requesterClient->name ?? $row->requesterUser->name]) }}
                                · {{ $row->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                            </p>
                            @if ($row->notes)
                                <p class="mt-2 whitespace-pre-wrap text-slate-600 dark:text-slate-300">{{ $row->notes }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <form method="POST" action="{{ route('clients.account-requests.approve', $row) }}">
                                @csrf
                                <x-primary-button type="submit" class="!normal-case">{{ __('Approve & create account') }}</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('clients.account-requests.reject', $row) }}" onsubmit="return confirm({{ json_encode(__('Reject this request?')) }})">
                                @csrf
                                <x-secondary-button type="submit" class="!normal-case">{{ __('Reject') }}</x-secondary-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No pending requests.') }}</p>
                @endforelse
            </div>

            @if ($recent->isNotEmpty())
                <div class="flow-panel overflow-hidden p-0">
                    <div class="border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/60">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Recent decisions') }}</h3>
                    </div>
                    <x-flow.table>
                        <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                            @foreach ($recent as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white text-start">{{ $row->name }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-start">{{ $row->email }}</td>
                                    <td class="px-4 py-3 text-start">
                                        @if ($row->status === 'approved')
                                            <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ __('Approved') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-500/15 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('Rejected') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-start">{{ $row->reviewed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-flow.table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
