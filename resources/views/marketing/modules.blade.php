@extends('layouts.marketing')

@section('title', config('app.name').' — '.__('marketing_modules_title'))
@section('meta_description', __('marketing_modules_meta'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Modules'),
        'title' => __('marketing_modules_title'),
        'lead' => __('marketing_modules_lead'),
        'maxWidth' => 'max-w-6xl',
        'centered' => true,
    ])

    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 pb-24 pt-8 sm:px-10 lg:px-12">
            <form method="GET" id="modules-market-filter" class="mb-10 flex flex-wrap items-end justify-center gap-4">
                <div>
                    <label for="region" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketing_modules_filter_region') }}</label>
                    <select id="region" name="region" class="mt-1 min-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800" onchange="onModulesRegionChange(this)">
                        @foreach ($marketingRegions as $code => $meta)
                            <option value="{{ $code }}" @selected($selectedRegion === $code)>{{ $regionService->regionLabel($code) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="country" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketing_modules_filter_country') }}</label>
                    <select id="country" name="country" class="mt-1 min-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800" onchange="this.form.submit()">
                        <option value="">{{ __('marketing_modules_filter_country_all') }}</option>
                        @foreach ($countryOptions as $iso => $label)
                            <option value="{{ $iso }}" @selected($selectedCountry === $iso)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('marketing_modules_filter_category') }}</label>
                    <select id="category" name="category" class="mt-1 min-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800" onchange="this.form.submit()">
                        <option value="">{{ __('marketing_modules_filter_category_all') }}</option>
                        @foreach ($categoryOptions as $category)
                            <option value="{{ $category->value }}" @selected($selectedCategory === $category->value)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="currency" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Display currency') }}</label>
                    @if ($selectedRegion === 'global')
                        <select id="currency" name="currency" class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800" onchange="this.form.submit()">
                            @foreach ($supportedCurrencies as $code)
                                <option value="{{ $code }}" @selected($displayCurrency === $code)>{{ $currencyLabels[$code] ?? $code }}</option>
                            @endforeach
                        </select>
                    @else
                        <div id="currency" class="mt-1 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-800">
                            {{ $currencyLabels[$displayCurrency] ?? $displayCurrency }}
                        </div>
                        <input type="hidden" name="currency" value="{{ $displayCurrency }}" />
                    @endif
                </div>
            </form>

            @if ($selectedCountry || $selectedRegion !== 'global')
                <p class="mb-8 text-center text-sm text-slate-600">
                    {{ __('marketing_modules_filter_active', [
                        'region' => $regionService->regionLabel($selectedRegion),
                        'country' => $selectedCountry ? ($countryOptions[$selectedCountry] ?? $selectedCountry) : __('marketing_modules_filter_country_all'),
                        'currency' => $displayCurrency,
                    ]) }}
                </p>
            @endif

            @push('head')
                <script>
                    const modulesRegionCurrencies = @json(collect($marketingRegions)->mapWithKeys(fn ($meta, $code) => [$code => $meta['currency'] ?? 'USD']));
                    function onModulesRegionChange(select) {
                        const form = document.getElementById('modules-market-filter');
                        const country = form.querySelector('#country');
                        if (country) {
                            country.value = '';
                        }
                        const region = select.value;
                        const currencyInput = form.querySelector('input[name="currency"]');
                        const currencySelect = form.querySelector('select#currency');
                        const code = modulesRegionCurrencies[region] || 'USD';
                        if (currencyInput) {
                            currencyInput.value = code;
                        }
                        if (currencySelect && region !== 'global') {
                            currencySelect.value = code;
                        }
                        form.submit();
                    }
                </script>
            @endpush

            @if ($grouped->isEmpty())
                <div class="mx-auto max-w-xl rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center">
                    <p class="text-sm font-medium text-slate-700">{{ __('marketing_modules_empty') }}</p>
                </div>
            @else
                @if ($selectedCategory === '' && $grouped->count() > 1)
                    <nav class="mb-10 flex flex-wrap justify-center gap-2" aria-label="{{ __('marketing_modules_filter_category') }}">
                        @foreach ($grouped as $categoryKey => $items)
                            <a
                                href="#module-category-{{ $categoryKey }}"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-800"
                            >
                                {{ $items->first()->category->label() }}
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $items->count() }}</span>
                            </a>
                        @endforeach
                    </nav>
                @endif

                <div class="space-y-14">
                    @foreach ($grouped as $categoryKey => $items)
                        <section id="module-category-{{ $categoryKey }}" class="scroll-mt-24">
                            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">{{ __('Category') }}</p>
                                    <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $items->first()->category->label() }}</h2>
                                </div>
                                <p class="text-sm font-medium text-slate-500">
                                    {{ trans_choice('marketing_modules_category_count', $items->count(), ['count' => $items->count()]) }}
                                </p>
                            </div>
                            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($items as $mod)
                                    @include('marketing.partials.module-card', [
                                        'mod' => $mod,
                                        'displayCurrency' => $displayCurrency,
                                        'catalogQuery' => array_filter([
                                            'region' => $selectedRegion,
                                            'country' => $selectedCountry,
                                            'currency' => $displayCurrency,
                                        ]),
                                    ])
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif

            <div class="mx-auto mt-16 max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-sm text-slate-600">{{ __('marketing_modules_cta_lead') }}</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex rounded-md bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Create workspace') }}</a>
                    @endif
                    <a href="{{ route('marketing.contact') }}" class="inline-flex rounded-md border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">{{ __('Contact sales') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
