<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $project->title }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">{{ __('Status') }}</p>
                        <x-flow.badge variant="primary">{{ $project->status->label() }}</x-flow.badge>
                    </div>
                    <div class="flex gap-1">
                        @include('provider.partials.icon-action', [
                            'href' => route('provider.projects.proposals.create', $project),
                            'label' => __('Send estimate'),
                            'icon' => 'fa-solid fa-file-invoice',
                            'variant' => 'primary',
                        ])
                        @include('provider.partials.icon-action', [
                            'href' => route('provider.projects.edit', $project),
                            'label' => __('Edit'),
                            'icon' => 'fa-regular fa-pen-to-square',
                        ])
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('Client') }}: {{ $project->client?->name ?? '—' }}</p>
                @php($pc = $project->company?->default_currency ?? 'USD')
                @if ($project->negotiated_price !== null)
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Negotiated price') }}: <span class="font-medium tabular-nums text-slate-800 dark:text-slate-200">{{ flowdesk_format_minor((int) $project->negotiated_price, $pc) }} {{ $pc }}</span></p>
                @endif
                @if ($project->final_deadline)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Final deadline') }}: {{ $project->final_deadline->format('Y-m-d') }}</p>
                @endif
                @if ($project->description)
                    <div class="flow-rich-text mt-4 text-sm text-slate-700 dark:text-slate-300">
                        {!! $project->description !!}
                    </div>
                @endif
            </div>

            <div class="flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Proposals') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40">
                                <th class="px-5 py-3 text-start">{{ __('Name') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Amount') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($project->proposals as $proposal)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 font-medium text-slate-900 dark:text-white text-start">{{ $proposal->name }}</td>
                                    <td class="px-5 py-4 text-end text-slate-700 dark:text-slate-300"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $proposal->amount, $proposal->currency) }} {{ $proposal->currency }}</span></td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @include('provider.partials.icon-action', [
                                                'href' => route('proposals.show', $proposal),
                                                'label' => __('View'),
                                                'icon' => 'fa-regular fa-eye',
                                            ])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-12 text-center text-slate-500">{{ __('No proposals yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('provider.projects.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">← {{ __('Back') }}</a>
        </div>
    </div>
</x-app-layout>
