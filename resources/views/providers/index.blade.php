<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Providers') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('Business providers')">
                <x-slot name="actions">
                    @if (Auth::user()->hasRole('company_admin'))
                        <a href="{{ route('settings.provider-recruitment') }}">
                            <x-secondary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                                <i class="fa-solid fa-link text-sm" aria-hidden="true"></i>
                                {{ __('Provider recruitment') }}
                            </x-secondary-button>
                        </a>
                    @endif
                    <a href="{{ route('providers.remittance-requests.index') }}">
                        <x-secondary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                            <i class="fa-solid fa-money-bill-transfer text-sm" aria-hidden="true"></i>
                            {{ __('provider_remittance_inbox_title') }}
                        </x-secondary-button>
                    </a>
                    <a href="{{ route('providers.create') }}">
                        <x-primary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                            <i class="fa-solid fa-user-tie text-sm" aria-hidden="true"></i>
                            {{ __('Add provider') }}
                        </x-primary-button>
                    </a>
                </x-slot>
            </x-flow.page-header>

            <form method="GET" class="mb-6 flex flex-wrap gap-3">
                <x-text-input name="q" :value="$q" class="max-w-md" placeholder="{{ __('Search providers…') }}" />
                <x-secondary-button type="submit" class="inline-flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                    {{ __('Search') }}
                </x-secondary-button>
            </form>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Phone or WhatsApp') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Role / specialty') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Commission') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Partnership') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Projects') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                        @forelse ($providers as $provider)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-start">{{ $provider->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">{{ $provider->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $provider->phone ?? '—' }}</span></td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">{{ $provider->job_title ?? '—' }}</td>
                                <td class="px-4 py-3 text-start">
                                    @if ($provider->commission_rate !== null)
                                        <span class="inline-flex rounded-full bg-cyan-500/15 px-2.5 py-0.5 text-xs font-semibold text-cyan-800 dark:text-cyan-200">
                                            {{ number_format((float) $provider->commission_rate * 100, 2) }}%
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300 text-start">
                                    @if ($provider->isPartnershipActive())
                                        <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 font-semibold text-emerald-800 dark:text-emerald-200">{{ __('Active') }}</span>
                                    @else
                                        {{ $provider->partnership_status->label() }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $provider->projects_count }}</span></td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                        @if (Auth::user()->hasRole('company_admin') && $provider->needsCompanyPartnershipSignature())
                                            <a
                                                href="{{ route('providers.partnership.show', $provider) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200/80 bg-amber-50 text-amber-800 shadow-sm transition hover:border-amber-300 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200"
                                                title="{{ __('Sign partnership') }}"
                                            >
                                                <i class="fa-solid fa-file-signature text-sm" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                        @if ($provider->partnership_provider_signed_at && Auth::user()->hasRole('company_admin'))
                                            <a
                                                href="{{ route('providers.partnership.signature', $provider) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                title="{{ __('View provider signature') }}"
                                            >
                                                <i class="fa-solid fa-signature text-sm" aria-hidden="true"></i>
                                            </a>
                                            <a
                                                href="{{ route('providers.partnership.contract', $provider) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                title="{{ __('View signed contract') }}"
                                            >
                                                <i class="fa-regular fa-file-lines text-sm" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('chat.open.provider', $provider) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('Chat') }}"
                                        >
                                            <i class="fa-regular fa-comments text-sm" aria-hidden="true"></i>
                                        </a>
                                        <a
                                            href="{{ route('providers.edit', $provider) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('Edit') }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('providers.destroy', $provider) }}" method="POST" class="inline" onsubmit="return confirm({{ json_encode(__('Remove this provider?')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:text-rose-400"
                                                title="{{ __('Remove') }}"
                                            >
                                                <i class="fa-regular fa-trash-can text-sm" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No providers yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <div class="mt-6">{{ $providers->links() }}</div>
        </div>
    </div>
</x-app-layout>
