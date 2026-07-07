<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('My projects') }}</h2>
            <a href="{{ route('provider.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <a href="{{ route('provider.projects.create') }}">
                    <x-primary-button type="button">{{ __('New project') }}</x-primary-button>
                </a>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40">
                                <th class="px-5 py-3 text-start">{{ __('Name') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Client') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($projects as $project)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 font-medium text-slate-900 dark:text-white text-start">{{ $project->title }}</td>
                                    <td class="px-5 py-4 text-start"><x-flow.badge variant="neutral">{{ $project->status->label() }}</x-flow.badge></td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300 text-start">{{ $project->client?->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @include('provider.partials.icon-action', [
                                                'href' => route('provider.projects.show', $project),
                                                'label' => __('View'),
                                                'icon' => 'fa-regular fa-eye',
                                            ])
                                            @include('provider.partials.icon-action', [
                                                'href' => route('provider.projects.edit', $project),
                                                'label' => __('Edit'),
                                                'icon' => 'fa-regular fa-pen-to-square',
                                            ])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-slate-500">{{ __('No projects yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($projects->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $projects->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
