<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ \Illuminate\Support\Str::limit($ticket->title, 64) }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel p-8 space-y-4">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Subject') }}</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $ticket->title }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1"><x-flow.badge variant="primary">{{ $ticket->status->label() }}</x-flow.badge></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Opened by') }}</dt>
                        <dd class="mt-1 text-slate-900 dark:text-white">{{ $ticket->user?->name ?? '—' }}</dd>
                    </div>
                    @if ($ticket->client_id || $ticket->provider_id)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Related') }}</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">
                                @if ($ticket->client_id)
                                    {{ __('Client') }}: {{ $ticket->client?->name ?? '—' }}
                                @elseif ($ticket->provider_id)
                                    {{ __('Business provider') }}: {{ $ticket->provider?->name ?? '—' }}
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Created') }}</dt>
                        <dd class="mt-1 tabular-nums text-slate-700 dark:text-slate-300">{{ $ticket->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
                <div>
                    <h3 class="text-xs font-semibold uppercase text-slate-500">{{ __('Message') }}</h3>
                    <div class="mt-2 whitespace-pre-wrap text-sm text-slate-800 dark:text-slate-200">{{ $ticket->description }}</div>
                </div>
            </div>

            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                <div class="flow-panel p-8">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Update status') }}</h3>
                    <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="mt-4 flex flex-wrap items-end gap-4">
                        @csrf
                        @method('PATCH')
                        <div class="min-w-[12rem] flex-1">
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                @foreach (\App\Enums\SupportTicketStatus::cases() as $st)
                                    <option value="{{ $st->value }}" @selected($ticket->status === $st)>{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                    </form>
                </div>
            @endif

            <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Back to tickets') }}</a>
        </div>
    </div>
</x-app-layout>
