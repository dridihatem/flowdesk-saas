@if (! empty($modulePages))
    <nav
        class="mb-6 overflow-x-auto rounded-xl border border-slate-200/80 bg-white p-1 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40"
        aria-label="{{ __('Module sections') }}"
    >
        <ul class="flex min-w-max gap-1">
            @foreach ($modulePages as $navPage)
                @php
                    $isActive = ($currentPage ?? '') === $navPage['slug'];
                @endphp
                <li>
                    <a
                        href="{{ $navPage['route'] }}"
                        @class([
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition',
                            'bg-indigo-600 text-white shadow-sm' => $isActive,
                            'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' => ! $isActive,
                        ])
                    >
                        {{ $navPage['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
