<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Sequences')"
                :description="__('email_marketing_sequences_intro')"
            />

            <div class="mt-8">
                @if ($sequences->isEmpty())
                    @include('email-marketing.partials.empty', ['message' => __('No automations yet. Drip sequences and follow-ups will be configured here.')])
                @else
                    <div class="flow-panel overflow-hidden p-0">
                        <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                                <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($sequences as $s)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white text-start">{{ $s->name }}</td>
                                        <td class="px-4 py-3 capitalize text-slate-600 dark:text-slate-400 text-start">{{ $s->status }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-start">{{ $s->updated_at?->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $sequences->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
