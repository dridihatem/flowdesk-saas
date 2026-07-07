@php
    $m = $module ?? null;
    $priceMajor = old('price');
    if ($priceMajor === null && $m) {
        $priceMajor = flowdesk_major_amount_for_locale_input((int) $m->price_minor, $m->currency);
    }
@endphp

<div class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="slug" :value="__('admin_marketplace_module_slug')" />
            <x-text-input id="slug" name="slug" class="mt-1 block w-full font-mono" :value="old('slug', $m->slug ?? '')" required />
            <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_slug_hint') }}</p>
            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $m->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">{{ old('description', $m->description ?? '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_description_hint') }}</p>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="detail_content" :value="__('admin_marketplace_module_detail_content')" />
        <textarea id="detail_content" name="detail_content" rows="8" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('admin_marketplace_module_detail_content_placeholder') }}">{{ old('detail_content', $m->detail_content ?? '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_detail_content_hint') }}</p>
        <x-input-error :messages="$errors->get('detail_content')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
            <x-input-label for="module_image" :value="__('admin_marketplace_module_image')" />
            @if ($m?->image_path)
                <img src="{{ $m->imageUrl() }}" alt="" class="mt-2 h-24 w-24 rounded-lg border border-slate-200 object-cover" />
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500" @checked(old('remove_image')) />
                    {{ __('admin_marketplace_module_image_remove') }}
                </label>
            @endif
            <input id="module_image" name="module_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700" />
            <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_image_hint') }}</p>
            <x-input-error :messages="$errors->get('module_image')" class="mt-2" />
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
            <x-input-label for="module_cover" :value="__('admin_marketplace_module_cover')" />
            @if ($m?->cover_path)
                <img src="{{ $m->coverUrl() }}" alt="" class="mt-2 h-24 w-full max-w-xs rounded-lg border border-slate-200 object-cover" />
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remove_cover" value="1" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500" @checked(old('remove_cover')) />
                    {{ __('admin_marketplace_module_cover_remove') }}
                </label>
            @endif
            <input id="module_cover" name="module_cover" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700" />
            <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_cover_hint') }}</p>
            <x-input-error :messages="$errors->get('module_cover')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-input-label for="category" :value="__('Category')" />
            <select id="category" name="category" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" @selected(old('category', $m?->category?->value ?? 'general') === $cat->value)>{{ $cat->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="price" :value="__('Price')" />
            <x-text-input id="price" name="price" type="text" inputmode="decimal" class="mt-1 block w-full" :value="$priceMajor" required />
            <x-input-error :messages="$errors->get('price')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="currency" :value="__('Currency')" />
            <select id="currency" name="currency" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                @foreach ($currencies as $code)
                    <option value="{{ $code }}" @selected(old('currency', $m->currency ?? 'USD') === $code)>{{ $currencyLabels[$code] ?? $code }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="billing_period" :value="__('admin_marketplace_module_billing_period')" />
            <select id="billing_period" name="billing_period" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                @foreach ($billingPeriods as $period)
                    <option value="{{ $period->value }}" @selected(old('billing_period', $m?->billing_period?->value ?? 'monthly') === $period->value)>{{ $period->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('billing_period')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label :value="__('admin_marketplace_module_countries')" />
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_countries_hint') }}</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $selectedCountries = old('target_countries', $m?->target_countries ?? []);
                $selectedCountries = is_array($selectedCountries) ? $selectedCountries : [];
            @endphp
            @foreach ($countryOptions as $iso => $label)
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="target_countries[]"
                        value="{{ $iso }}"
                        class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500"
                        @checked(in_array($iso, $selectedCountries, true))
                    />
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('target_countries')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="icon" :value="__('admin_marketplace_module_icon')" />
            <x-text-input id="icon" name="icon" class="mt-1 block w-full font-mono" :value="old('icon', $m->icon ?? '')" placeholder="building" />
            <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_icon_hint') }}</p>
            <x-input-error :messages="$errors->get('icon')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="sort_order" :value="__('admin_email_template_model_sort_order')" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $m->sort_order ?? 0)" />
            <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="feature_bullets" :value="__('admin_marketplace_module_features')" />
        <textarea id="feature_bullets" name="feature_bullets" rows="5" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('admin_marketplace_module_features_placeholder') }}">{{ old('feature_bullets', isset($m) && is_array($m->feature_bullets) ? implode("\n", $m->feature_bullets) : '') }}</textarea>
        <x-input-error :messages="$errors->get('feature_bullets')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2">
        <input id="is_published" name="is_published" type="checkbox" value="1" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500" @checked(old('is_published', $m?->is_published ?? true)) />
        <x-input-label for="is_published" :value="__('admin_marketplace_module_published')" class="!mb-0" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
        <x-input-label for="module_zip" :value="__('admin_marketplace_module_zip')" />
        @if ($m?->zip_path)
            <p class="mt-1 text-sm text-emerald-700">
                <i class="fa-solid fa-file-zipper me-1" aria-hidden="true"></i>
                {{ __('admin_marketplace_module_zip_attached') }}
            </p>
            <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remove_zip" value="1" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500" @checked(old('remove_zip')) />
                {{ __('admin_marketplace_module_zip_remove') }}
            </label>
        @endif
        <input
            id="module_zip"
            name="module_zip"
            type="file"
            accept=".zip,application/zip"
            class="mt-2 block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700"
        />
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_module_zip_hint') }}</p>
        <x-input-error :messages="$errors->get('module_zip')" class="mt-2" />
    </div>
</div>
