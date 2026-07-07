<x-admin-layout :title="__('admin_invoice_pdf_templates_title')">
    <x-flow.page-header
        :title="__('admin_invoice_pdf_templates_title')"
        :description="__('admin_invoice_pdf_templates_intro')"
    />

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="flow-panel lg:col-span-4 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('admin_invoice_pdf_template_add') }}</h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('admin_invoice_pdf_template_key_hint') }}</p>

            <form method="POST" action="{{ route('admin.invoice-pdf-templates.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <x-input-label for="key" :value="__('Key')" />
                    <x-text-input id="key" name="key" class="mt-1 block w-full font-mono" :value="old('key')" :placeholder="__('e.g. corporate_blue')" required />
                    <x-input-error :messages="$errors->get('key')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="label" :value="__('Label')" />
                    <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label')" required />
                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                </div>

                @php
                    $colorFields = [
                        'primary_color' => __('admin_invoice_pdf_color_primary'),
                        'accent_color' => __('admin_invoice_pdf_color_accent'),
                        'table_header_bg' => __('admin_invoice_pdf_color_table_header'),
                        'border_color' => __('admin_invoice_pdf_color_border'),
                        'text_color' => __('admin_invoice_pdf_color_text'),
                        'muted_color' => __('admin_invoice_pdf_color_muted'),
                        'totals_grand_bg' => __('admin_invoice_pdf_color_totals_grand'),
                        'pay_box_bg' => __('admin_invoice_pdf_color_pay_box'),
                    ];
                @endphp

                @foreach ($colorFields as $field => $lbl)
                    <div>
                        <x-input-label :for="$field" :value="$lbl" />
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1 dark:border-slate-600" data-sync-hex="#{{ $field }}" />
                            <x-text-input :id="$field" :name="$field" class="block w-full font-mono" :value="old($field, $field === 'primary_color' ? '#18181b' : ($field === 'accent_color' ? '#0d9488' : ($field === 'table_header_bg' ? '#ffffff' : ($field === 'border_color' ? '#d4d4d8' : ($field === 'text_color' ? '#27272a' : ($field === 'muted_color' ? '#71717a' : ($field === 'totals_grand_bg' ? '#ffffff' : '#fafafa')))))))" @if (in_array($field, ['totals_grand_bg', 'pay_box_bg'], true)) placeholder="#optional" @else required @endif />
                        </div>
                        <x-input-error :messages="$errors->get($field)" class="mt-2" />
                    </div>
                @endforeach

                <div class="flex items-center gap-2">
                    <input id="compact_header" name="compact_header" type="checkbox" value="1" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500" checked />
                    <x-input-label for="compact_header" :value="__('admin_invoice_pdf_compact_header')" class="!mb-0" />
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                    <span>{{ __('Save') }}</span>
                </button>
            </form>
        </div>

        <div class="lg:col-span-8 space-y-6">
            <div class="flow-panel p-5">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('admin_invoice_pdf_template_builtin') }}</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('admin_invoice_pdf_template_builtin_blurb') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 font-mono text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">classic</span>
                </div>
            </div>

            @if ($library === [])
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('admin_invoice_pdf_templates_empty') }}</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($library as $presetKey => $t)
                        @php
                            $primary = $t['primary_color'] ?? '#0f172a';
                            $accent = $t['accent_color'] ?? '#2563eb';
                            $th = $t['table_header_bg'] ?? '#e2e8f0';
                        @endphp
                        <div class="flow-panel overflow-hidden p-0">
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $t['label'] ?? $presetKey }}</div>
                                        <div class="mt-1 font-mono text-[11px] text-slate-500">{{ $presetKey }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.invoice-pdf-templates.destroy', ['key' => $presetKey]) }}" onsubmit="return confirm(@json(__('admin_invoice_pdf_template_remove_confirm')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-700 shadow-sm transition hover:bg-rose-50 hover:text-rose-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                            <i class="fa-regular fa-trash-can text-sm" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <span class="h-8 w-8 rounded-md border border-slate-200 shadow-inner dark:border-slate-600" style="background: {{ $primary }}"></span>
                                    <span class="h-8 w-8 rounded-md border border-slate-200 shadow-inner dark:border-slate-600" style="background: {{ $accent }}"></span>
                                    <span class="h-8 w-8 rounded-md border border-slate-200 shadow-inner dark:border-slate-600" style="background: {{ $th }}"></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
