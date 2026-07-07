@props(['cards' => []])

@if (count($cards) > 0)
    <div class="grid gap-px bg-slate-200/70 dark:bg-slate-700/60 sm:grid-cols-2">
        @foreach ($cards as $card)
            @if (Route::has($card['route']))
                <a
                    href="{{ route($card['route']) }}"
                    class="group flex items-start gap-3 bg-white p-4 transition hover:bg-indigo-50/50 dark:bg-slate-900 dark:hover:bg-indigo-950/30"
                >
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500/15 to-violet-500/10 text-indigo-600 transition group-hover:from-indigo-500/25 group-hover:to-violet-500/20 dark:from-indigo-400/20 dark:to-violet-400/10 dark:text-indigo-300">
                        <x-flow.nav-icon :name="$card['icon']" class="!text-[0.95rem]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] }}</span>
                            <i class="fa-solid fa-arrow-right ms-auto shrink-0 text-xs text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500 dark:text-slate-600" aria-hidden="true"></i>
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $card['summary'] }}</span>
                    </span>
                </a>
            @endif
        @endforeach
    </div>
@endif
