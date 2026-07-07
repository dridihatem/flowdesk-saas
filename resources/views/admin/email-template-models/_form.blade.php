@php
    $m = $model ?? null;
@endphp

<div class="space-y-4">
    <div>
        <x-input-label for="slug" :value="__('admin_email_template_model_slug')" />
        <x-text-input
            id="slug"
            name="slug"
            class="mt-1 block w-full font-mono"
            :value="old('slug', $m->slug ?? '')"
            :placeholder="__('e.g. summer_sale')"
            required
        />
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_email_template_model_slug_hint') }}</p>
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $m->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Category')" />
        <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category', $m->category ?? '')" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('admin_email_template_model_sort_order')" />
        <x-text-input
            id="sort_order"
            name="sort_order"
            type="number"
            class="mt-1 block w-full max-w-[12rem]"
            :value="old('sort_order', $m->sort_order ?? 0)"
            min="0"
        />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2">
        <input
            id="is_active"
            name="is_active"
            type="checkbox"
            value="1"
            class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500"
            @checked(old('is_active', $m?->is_active ?? true))
        />
        <x-input-label for="is_active" :value="__('admin_email_template_model_active')" class="!mb-0" />
    </div>

    <div>
        <x-input-label for="body_html" :value="__('admin_email_template_model_body_html')" />
        <textarea
            id="body_html"
            name="body_html"
            rows="18"
            class="mt-1 block w-full rounded-lg border border-slate-300 font-mono text-xs shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            required
        >{{ old('body_html', $m->body_html ?? '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin_email_template_model_body_hint') }}</p>
        <x-input-error :messages="$errors->get('body_html')" class="mt-2" />
    </div>
</div>
