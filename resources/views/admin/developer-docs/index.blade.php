<x-admin-layout>
    <x-flow.page-header
        :title="__('Developer documentation')"
        :description="__('Project structure, conventions, and onboarding guide for developers working on FlowDesk SaaS.')"
    />

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($stack as $item)
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                {{ $item }}
            </span>
        @endforeach
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-12">
        <nav class="lg:col-span-3">
            <div class="flow-panel p-4">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Sections') }}</p>
                <ul class="mt-3 space-y-1">
                    @foreach ($sections as $section)
                        <li>
                            <a
                                href="{{ route('admin.developer-docs.index', ['section' => $section['id']]) }}"
                                @class([
                                    'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-900/50' => $activeId === $section['id'],
                                    'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60' => $activeId !== $section['id'],
                                ])
                            >
                                <i class="fa-solid {{ $section['icon'] }} w-4 text-xs opacity-80" aria-hidden="true"></i>
                                <span>{{ $section['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="flow-panel mt-4 p-4">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('Repository guides') }}</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($repoGuides as $guide)
                        <li class="flex items-start gap-2">
                            <i @class([
                                'fa-regular fa-file-lines mt-0.5 text-xs',
                                'text-emerald-600' => $guide['exists'],
                                'text-slate-400' => ! $guide['exists'],
                            ]) aria-hidden="true"></i>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $guide['title'] }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ $guide['file'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-slate-500">{{ __('developer_docs_repo_hint') }}</p>
            </div>
        </nav>

        <div class="lg:col-span-9 space-y-6">
            @foreach ($trees as $block)
                <div class="flow-panel overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                        <h3 class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $block['root'] }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($block['rows'] as $row)
                            <div
                                class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-5 py-2.5"
                                style="padding-inline-start: {{ 1.25 + ($row['depth'] * 1.25) }}rem"
                            >
                                <span class="font-mono text-sm text-emerald-800 dark:text-emerald-300">{{ $row['path'] }}</span>
                                @if (! empty($row['hint']))
                                    <span class="text-sm text-slate-600 dark:text-slate-400">— {{ $row['hint'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($activeId === 'overview')
                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Common workflows') }}</h3>
                    <div class="mt-4 space-y-6">
                        @foreach ($workflows as $workflow)
                            <div>
                                <h4 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ $workflow['title'] ?? '' }}</h4>
                                <ol class="mt-2 list-decimal space-y-1 ps-5 text-sm text-slate-700 dark:text-slate-300">
                                    @foreach ($workflow['steps'] ?? [] as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/80 p-5 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                    <p class="font-semibold">{{ __('developer_docs_quick_start_title') }}</p>
                    <ol class="mt-3 list-decimal space-y-2 ps-5">
                        <li><code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs">composer install && npm install</code></li>
                        <li><code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs">cp .env.example .env && php artisan key:generate</code></li>
                        <li><code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs">php artisan migrate --seed</code></li>
                        <li><code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs">npm run build</code> {{ __('or') }} <code class="rounded bg-white/70 px-1.5 py-0.5 font-mono text-xs">npm run dev</code></li>
                        <li>{{ __('developer_docs_quick_start_admin') }}</li>
                    </ol>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
