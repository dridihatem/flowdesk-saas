<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Messages') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-4">
            @if (auth()->user()->hasRole('client') && $company)
                <div class="rounded-2xl border border-indigo-200/80 bg-gradient-to-r from-indigo-50/90 to-white px-5 py-4 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-slate-900/50">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Your company contact') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $company->name }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_chat_company_hint') }}</p>
                </div>
            @endif

            @if ($threads->isEmpty())
                <div class="flow-panel p-8 text-center text-sm text-slate-600 dark:text-slate-400">
                    @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                        {{ __('No conversations yet. Open a client or provider record and choose Chat, or use the links below.') }}
                    @else
                        {{ __('No conversation yet. Your workspace will appear here once messaging is enabled.') }}
                    @endif
                </div>
            @else
                <ul class="space-y-2">
                    @foreach ($threads as $thread)
                        <li>
                            <a href="{{ route('chat.show', $thread) }}" class="flow-panel flex items-center justify-between p-4 transition hover:border-indigo-300 dark:hover:border-indigo-600">
                                <div>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $thread->resolveDisplayNameFor(auth()->user()) }}</span>
                                    @if (auth()->user()->hasRole('client') && $thread->company)
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Company') }} · {{ $thread->company->name }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-500">
                                    @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                        {{ $thread->type === \App\Models\ChatThread::TYPE_CLIENT ? __('Client') : __('Provider') }}
                                    @else
                                        {{ __('Open') }} →
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                <div class="flow-panel p-6 text-sm text-slate-600 dark:text-slate-400">
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ __('Tip') }}</p>
                    <p class="mt-1">{{ __('Start a thread from the Clients or Providers list using the message icon, or open an existing thread above.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
