@php
    $primary = $form->meta['widget']['primary'] ?? '#4f46e5';
    $theme = $form->meta['widget']['theme'] ?? 'light';
    $isDark = $theme === 'dark';
    $layout = $form->layout ?? 'simple';
    $bgClass = $isDark ? 'bg-slate-900 text-slate-100' : 'bg-white text-slate-900';
    $mutedClass = $isDark ? 'text-slate-400' : 'text-slate-500';
    $inputClass = $isDark
        ? 'rounded-lg border border-slate-600 bg-slate-800/80 text-slate-100 placeholder-slate-500'
        : 'rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400';
@endphp
<aside
    class="flow-form-review-sidebar rounded-2xl border border-slate-200/80 bg-slate-50/90 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/40 dark:ring-white/10 lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto"
    aria-label="{{ __('Form preview') }}"
>
    <div class="border-b border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Form review') }}</h3>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Approximate embed appearance') }}</p>
    </div>

    <div class="p-4">
        <div class="{{ $bgClass }} rounded-xl border p-4 shadow-inner ring-1 {{ $isDark ? 'border-slate-700 ring-white/5' : 'border-slate-200/90 ring-slate-900/5' }}" style="--flow-preview-accent: {{ e($primary) }};">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-2 border-b pb-3 {{ $isDark ? 'border-slate-700' : 'border-slate-200' }}">
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold">{{ $form->name }}</p>
                    <p class="mt-0.5 text-xs {{ $mutedClass }}">{{ $form->id }}</p>
                </div>
                <span
                    class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                    style="background: color-mix(in srgb, {{ e($primary) }} 18%, transparent); color: {{ e($primary) }};"
                >
                    {{ $form->status === 'published' ? __('Published') : __('Draft') }}
                </span>
            </div>

            @if ($form->fields->isEmpty())
                <p class="text-center text-sm {{ $mutedClass }} py-8">{{ __('No fields yet. Add fields in the editor.') }}</p>
            @else
                <div class="space-y-4">
                    @if ($layout === 'wizard')
                        @foreach ($form->fields->sortBy('sort_order')->groupBy(fn ($f) => (int) $f->step)->sortKeys() as $step => $group)
                            <div>
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wider {{ $mutedClass }}">{{ __('Step') }} {{ $step }}</p>
                                <div class="space-y-3">
                                    @foreach ($group->sortBy('sort_order') as $field)
                                        @include('forms.partials.form-review-field', ['field' => $field, 'inputClass' => $inputClass, 'mutedClass' => $mutedClass])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="space-y-3">
                            @foreach ($form->fields->sortBy('sort_order') as $field)
                                @include('forms.partials.form-review-field', ['field' => $field, 'inputClass' => $inputClass, 'mutedClass' => $mutedClass])
                            @endforeach
                        </div>
                    @endif

                    @if ($form->meta['captcha']['enabled'] ?? false)
                        <div class="rounded-lg border p-3 {{ $isDark ? 'border-slate-700 bg-slate-800/50' : 'border-slate-200 bg-slate-50/80' }}">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 {{ $mutedClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                <p class="text-[11px] font-medium {{ $mutedClass }}">{{ __('Math CAPTCHA') }}</p>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="rounded bg-white/80 px-2 py-1 text-xs font-bold tabular-nums text-slate-800 shadow-sm ring-1 ring-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:ring-slate-600">7 + 3 = ?</span>
                                <input
                                    type="text"
                                    disabled
                                    class="w-16 px-2 py-1 text-xs {{ $inputClass }}"
                                    placeholder="10"
                                />
                            </div>
                        </div>
                    @endif

                    <button
                        type="button"
                        disabled
                        class="mt-2 w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white opacity-90 shadow-sm"
                        style="background-color: {{ e($primary) }};"
                    >
                        {{ __('Submit') }}
                    </button>
                </div>
            @endif
        </div>

        <dl class="mt-4 space-y-2 text-xs text-slate-600 dark:text-slate-400">
            <div class="flex justify-between gap-2">
                <dt>{{ __('Layout') }}</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $layout === 'wizard' ? __('Wizard') : __('Simple') }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt>{{ __('Widget theme') }}</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $theme === 'dark' ? __('Dark') : __('Light') }}</dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt>{{ __('Math CAPTCHA') }}</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-200">{{ ($form->meta['captcha']['enabled'] ?? false) ? __('Enabled') : __('Disabled') }}</dd>
            </div>
        </dl>
    </div>
</aside>
