<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Edit form') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="w-full max-w-12xl sm:px-6 lg:px-8">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-10 xl:gap-12">
                <div class="min-w-0 flex-1 space-y-10">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Form details') }}</h3>
                <form method="POST" action="{{ route('forms.update', $form) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="name" :value="__('Form name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $form->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="draft" @selected(old('status', $form->status) === 'draft')>{{ __('Draft') }}</option>
                            <option value="published" @selected(old('status', $form->status) === 'published')>{{ __('Published') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="layout" :value="__('Layout')" />
                        <select id="layout" name="layout" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="simple" @selected(old('layout', $form->layout ?? 'simple') === 'simple')>{{ __('Simple (single page)') }}</option>
                            <option value="wizard" @selected(old('layout', $form->layout ?? 'simple') === 'wizard')>{{ __('Wizard (steps)') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Wizard groups fields by step number below.') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="widget_primary" :value="__('Widget accent color')" />
                            <x-text-input id="widget_primary" name="widget_primary" type="text" class="mt-1 block w-full" :value="old('widget_primary', $form->meta['widget']['primary'] ?? '#4f46e5')" placeholder="#4f46e5" />
                        </div>
                        <div>
                            <x-input-label for="widget_theme" :value="__('Widget theme')" />
                            <select id="widget_theme" name="widget_theme" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="light" @selected(old('widget_theme', $form->meta['widget']['theme'] ?? 'light') === 'light')>{{ __('Light') }}</option>
                                <option value="dark" @selected(old('widget_theme', $form->meta['widget']['theme'] ?? 'light') === 'dark')>{{ __('Dark') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('Math CAPTCHA') }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Require visitors to solve a simple math question before submitting. Blocks bots without third-party services.') }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="hidden" name="captcha_enabled" value="0" />
                                <input type="checkbox" name="captcha_enabled" value="1" class="peer sr-only" @checked(old('captcha_enabled', $form->meta['captcha']['enabled'] ?? false)) />
                                <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-slate-700 dark:after:border-slate-600 dark:peer-checked:bg-indigo-500"></div>
                            </label>
                        </div>
                    </div>
                    <x-primary-button>{{ __('Save form') }}</x-primary-button>
                </form>

                <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Widget version') }}: <strong class="text-slate-900 dark:text-white">{{ $form->widget_version }}</strong>
                        · {{ __('Views (30d)') }}: {{ $widgetViews }} · {{ __('Submits (30d)') }}: {{ $widgetSubmits }}
                    </div>
                    <form method="POST" action="{{ route('forms.bump-version', $form) }}" onsubmit="return confirm({{ json_encode(__('Bump version for breaking embed changes?')) }})">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Bump widget version') }}</x-secondary-button>
                    </form>
                    <a href="{{ route('forms.submissions.index', $form) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('View submissions') }}</a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Embed on your website') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Paste this snippet on any page. The form ID below is this form’s ULID. Keep the form published for the widget to load.') }}
                    <a href="{{ route('settings.widget-embed') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Generate or rotate your API token') }}</a>
                    {{ __('under Widget embed if needed.') }}
                </p>
                @if (! $hasApiToken)
                    <p class="mt-2 text-sm text-amber-800 dark:text-amber-200/90">{{ __('No API token on file. Generate one under Widget embed and replace fd_live_YOUR_COMPANY_API_TOKEN in the snippet.') }}</p>
                @endif
                <div class="mt-4">
                    @include('forms.partials.widget-embed-snippet', [
                        'baseUrl' => $baseUrl,
                        'formId' => $form->id,
                        'revealedToken' => $apiTokenPlain ?? null,
                        'codeId' => 'flowdesk-form-edit-embed',
                    ])
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Fields') }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Drag rows to reorder. Required fields are validated on embed submit.') }}</p>

                <form id="form-field-reorder-form" method="POST" action="{{ route('forms.fields.reorder', $form) }}" class="hidden">@csrf</form>

                <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                            <tr>
                                <th class="w-8 px-2 py-2 text-start"></th>
                                <th class="px-4 py-2 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Label') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Type') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Step') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Required') }}</th>
                                <th class="px-4 py-2 text-end text-xs font-semibold uppercase text-slate-500">{{ __('') }}</th>
                            </tr>
                        </thead>
                        <tbody id="form-field-rows" class="divide-y divide-slate-200 dark:divide-slate-700">
                            @php
                                $typeLabels = [
                                    'text' => __('Text'), 'email' => __('Email'), 'textarea' => __('Textarea'),
                                    'number' => __('Number'), 'tel' => __('Tel'), 'url' => __('URL'),
                                    'date' => __('Date'), 'radio' => __('Radio'), 'checkbox' => __('Checkbox'),
                                    'select' => __('Select (dropdown)'), 'file' => __('File upload'),
                                    'heading' => __('Heading'), 'paragraph' => __('Paragraph'),
                                ];
                            @endphp
                            @foreach ($form->fields as $field)
                                <tr data-field-id="{{ $field->id }}">
                                    <td class="px-2 py-2 text-slate-400 text-start">
                                        <span class="drag-handle inline-block cursor-grab select-none" title="{{ __('Drag') }}">⋮⋮</span>
                                    </td>
                                    <td class="px-4 py-2 text-start">
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ $field->name }}</span>
                                        @if(! empty($field->meta['options']))
                                            <span class="ml-1 text-[10px] text-slate-400 dark:text-slate-500">({{ count($field->meta['options']) }} {{ __('Options') }})</span>
                                        @endif
                                        @if(! empty($field->meta['placeholder']))
                                            <span class="block text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[14rem]">{{ $field->meta['placeholder'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-start">
                                        <x-flow.badge :variant="in_array($field->type, ['heading', 'paragraph']) ? 'neutral' : 'info'">{{ $typeLabels[$field->type] ?? $field->type }}</x-flow.badge>
                                    </td>
                                    <td class="px-4 py-2 text-start">
                                        <form method="POST" action="{{ route('forms.fields.update', [$form, $field]) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="step" value="{{ old('step', $field->step) }}" min="0" max="50" class="w-16 rounded border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
                                            <x-secondary-button type="submit" class="!py-1 !px-2 text-xs">{{ __('Set') }}</x-secondary-button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-2 text-start">
                                        @if(in_array($field->type, ['heading', 'paragraph']))
                                            <span class="text-xs text-slate-400">—</span>
                                        @else
                                            {{ $field->required ? __('Yes') : __('No') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-end">
                                        <form action="{{ route('forms.fields.destroy', [$form, $field]) }}" method="POST" onsubmit="return confirm({{ json_encode(__('Remove this field?')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Remove') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $fieldTypeGroups = [
                        __('Input') => [
                            'text' => __('Text'),
                            'email' => __('Email'),
                            'number' => __('Number'),
                            'tel' => __('Tel'),
                            'url' => __('URL'),
                            'date' => __('Date'),
                            'textarea' => __('Textarea'),
                        ],
                        __('Choice') => [
                            'radio' => __('Radio'),
                            'checkbox' => __('Checkbox'),
                            'select' => __('Select (dropdown)'),
                        ],
                        __('Media') => [
                            'file' => __('File upload'),
                        ],
                        __('Layout') => [
                            'heading' => __('Heading'),
                            'paragraph' => __('Paragraph'),
                        ],
                    ];
                    $optionTypes = ['radio', 'checkbox', 'select'];
                    $decorativeTypes = ['heading', 'paragraph'];
                @endphp

                <form method="POST" action="{{ route('forms.fields.store', $form) }}" class="mt-8 rounded-xl border border-dashed border-slate-300/80 p-6 dark:border-slate-600/80" id="add-field-form">
                    @csrf
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Add field') }}</h4>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="field_name" :value="__('Field label')" />
                            <x-text-input id="field_name" name="name" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="field_type" :value="__('Type')" />
                            <select id="field_type" name="type" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" onchange="window.__fdToggleFieldOptions && window.__fdToggleFieldOptions()">
                                @foreach ($fieldTypeGroups as $groupLabel => $types)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($types as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="field_placeholder" :value="__('Placeholder')" />
                            <x-text-input id="field_placeholder" name="placeholder" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="field_step" :value="__('Step (wizard)')" />
                            <x-text-input id="field_step" name="step" type="number" class="mt-1 block w-full" value="0" min="0" max="50" />
                        </div>
                        <div id="field-options-wrap" class="sm:col-span-2 hidden">
                            <x-input-label for="field_options" :value="__('Options (one per line)')" />
                            <textarea id="field_options" name="options" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" placeholder="Option A&#10;Option B&#10;Option C"></textarea>
                        </div>
                        <div class="flex items-center gap-2 sm:col-span-2" id="field-required-wrap">
                            <input id="required" name="required" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800">
                            <x-input-label for="required" :value="__('Required')" />
                        </div>
                        <div id="field-decorative-hint" class="sm:col-span-2 hidden">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Decorative (not submitted)') }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-secondary-button type="submit">{{ __('Add field') }}</x-secondary-button>
                    </div>
                </form>

                <script>
                    window.__fdToggleFieldOptions = function () {
                        var t = document.getElementById('field_type').value;
                        var optTypes = @json($optionTypes);
                        var decoTypes = @json($decorativeTypes);
                        document.getElementById('field-options-wrap').classList.toggle('hidden', !optTypes.includes(t));
                        document.getElementById('field-required-wrap').classList.toggle('hidden', decoTypes.includes(t));
                        document.getElementById('field-decorative-hint').classList.toggle('hidden', !decoTypes.includes(t));
                    };
                    document.addEventListener('DOMContentLoaded', window.__fdToggleFieldOptions);
                </script>
            </div>

            <div class="text-sm">
                <a href="{{ route('forms.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">← {{ __('Back to forms') }}</a>
            </div>
                </div>

                <div class="w-full shrink-0 lg:w-80 xl:w-96">
                    @include('forms.partials.form-review-sidebar', ['form' => $form])
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/form-builder.js')
    @endpush
</x-app-layout>
