<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Activity & audit trail')"
                :description="__('Recent actions recorded for your workspace (from audit logs).')"
            />

            <div class="space-y-3">
                @forelse ($logs as $log)
                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $log->action }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $log->created_at?->diffForHumans() }}
                                    @if ($log->user)
                                        · {{ $log->user->name }}
                                    @endif
                                </p>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ $log->auditable_type ? class_basename($log->auditable_type) : __('System') }}
                            </span>
                        </div>
                        @if ($log->properties)
                            <pre class="mt-3 max-h-32 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-950/50 dark:text-slate-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/50 px-6 py-16 text-center text-sm text-slate-500 dark:border-slate-600 dark:bg-slate-900/30 dark:text-slate-400">
                        {{ __('No activity recorded yet.') }}
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
