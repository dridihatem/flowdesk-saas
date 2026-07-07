<div class="space-y-4 border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Provider profile') }}</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" :value="__('Phone or WhatsApp')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $provider?->phone ?? '')" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>
        <div>
            <x-input-label for="website" :value="__('Website URL')" />
            <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $provider?->website ?? '')" placeholder="https://…" />
            <x-input-error class="mt-2" :messages="$errors->get('website')" />
        </div>
    </div>
    <div>
        <x-input-label for="job_title" :value="__('Role / specialty')" />
        <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $provider?->job_title ?? '')" :placeholder="__('e.g. Contractor, Logistics partner')" />
        <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
    </div>
    <div>
        <x-input-label for="description" :value="__('Bio or services description')" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('description', $provider?->description ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>
</div>
