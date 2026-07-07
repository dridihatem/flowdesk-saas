<x-admin-layout :title="__('Create plan')">
    <div class="mb-6">
        <a
            href="{{ route('admin.plans.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Back to plans') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="__('Create plan')"
        :description="__('Create a new subscription plan. Price is entered in USD and auto-converted using currency rates.')"
    />

    <div class="flow-panel max-w-xl p-8">
        <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="slug" :value="__('Slug')" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full font-mono" :value="old('slug')" required />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="base_price_usd" :value="__('Base price (USD) / month')" />
                <x-text-input id="base_price_usd" name="base_price_usd" type="number" class="mt-1 block w-full" :value="old('base_price_usd', 29)" required min="0" />
                <p class="mt-1 text-xs text-slate-500">{{ __('This is the default price in USD. We convert using your USD→currency rate.') }}</p>
                <x-input-error :messages="$errors->get('base_price_usd')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <select id="currency" name="currency" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" required>
                    @foreach (($currencies ?? ['USD']) as $code)
                        @php($label = ($currencyLabels[$code] ?? $code))
                        <option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button type="submit">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                        <span>{{ __('Create') }}</span>
                    </span>
                </x-primary-button>
            </div>
        </form>
    </div>
</x-admin-layout>

