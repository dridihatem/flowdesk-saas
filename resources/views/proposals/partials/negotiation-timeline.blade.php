@if ($proposal->negotiations->isNotEmpty())
<div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
    <div class="border-b border-slate-200/80 bg-slate-50/90 px-6 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">
                <i class="fa-solid fa-comments text-sm" aria-hidden="true"></i>
            </span>
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Negotiation timeline') }}</h3>
            </div>
        </div>
    </div>
    <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
        @foreach ($proposal->negotiations as $n)
            <li class="px-6 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ __('negotiation_status.'.$n->status->value) }}
                        </span>
                        @if ($n->amount !== null)
                            <p class="mt-2 text-lg font-bold tabular-nums text-slate-900 dark:text-white">
                                {{ flowdesk_format_minor((int) $n->amount, $n->currency) }}
                                <span class="text-sm font-normal text-slate-500">{{ strtoupper($n->currency) }}</span>
                            </p>
                        @endif
                        @if ($n->notes)
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $n->notes }}</p>
                        @endif
                    </div>
                    <time class="shrink-0 text-xs tabular-nums text-slate-500 dark:text-slate-400" datetime="{{ $n->created_at?->toIso8601String() }}">
                        {{ $n->created_at?->format('Y-m-d H:i') }}
                    </time>
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif
