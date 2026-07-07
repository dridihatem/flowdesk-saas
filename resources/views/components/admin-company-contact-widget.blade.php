<div
    class="fixed bottom-6 end-6 z-[200] flex max-w-sm flex-col items-end"
    x-data="{ open: false }"
>
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="mb-3 w-[min(100vw-2rem,22rem)] overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-900/5"
    >
        <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-900 to-slate-800 px-4 py-3">
            <p class="text-sm font-semibold text-white">{{ __('Contact workspaces') }}</p>
            <p class="mt-1 text-xs leading-relaxed text-slate-300">{{ __('Contact workspaces widget hint') }}</p>
        </div>
        <div class="max-h-72 overflow-y-auto p-2">
            @forelse ($companies as $row)
                @php($c = $row['company'])
                <div class="flex items-center justify-between gap-2 rounded-xl px-2 py-2 text-sm hover:bg-slate-50">
                    <a
                        href="{{ route('admin.companies.show', $c) }}"
                        class="min-w-0 flex-1 truncate font-medium text-slate-800 hover:text-emerald-700"
                    >
                        {{ $c->name }}
                    </a>
                    @if ($row['contact_email'])
                        <a
                            href="mailto:{{ $row['contact_email'] }}?subject={{ rawurlencode(config('app.name').' — '.$c->name) }}"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-emerald-700 transition hover:bg-emerald-50"
                            title="{{ __('Email') }}"
                        >
                            <i class="fa-solid fa-envelope text-sm" aria-hidden="true"></i>
                            <span class="sr-only">{{ __('Email') }}</span>
                        </a>
                    @else
                        <span class="shrink-0 text-xs text-slate-400" title="{{ __('No contact email on file') }}">—</span>
                    @endif
                </div>
            @empty
                <p class="px-3 py-4 text-center text-sm text-slate-500">{{ __('No companies yet.') }}</p>
            @endforelse
        </div>
        <div class="border-t border-slate-100 bg-slate-50/90 px-3 py-2">
            <a href="{{ route('admin.companies.index') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                {{ __('All companies') }} →
            </a>
        </div>
    </div>

    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-xl text-white shadow-lg shadow-emerald-900/30 ring-2 ring-white transition hover:from-emerald-400 hover:to-teal-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
        title="{{ __('Contact workspaces') }}"
    >
        <i class="fa-solid fa-comments" aria-hidden="true"></i>
        <span class="sr-only">{{ __('Contact workspaces') }}</span>
    </button>
</div>
