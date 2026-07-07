@php
    use App\Services\PlanLimitService;

    $periodByMonths = $plan->periodPrices->keyBy('period_months');
    $defaultPeriodMajor = function (int $m) use ($periodByMonths, $plan): string {
        $row = $periodByMonths->get($m);
        $cur = $plan->currency;
        if ($row !== null) {
            return flowdesk_major_amount_for_input((int) $row->price_minor, $cur);
        }
        $scale = flowdesk_currency_minor_scale($cur);
        $monthlyMinor = (int) round(((float) $plan->price_monthly) * $scale);

        return flowdesk_major_amount_for_input($monthlyMinor * $m, $cur);
    };
@endphp
@php
    $limitRows = old('limits');
    if (! is_array($limitRows)) {
        $limitRows = $plan->limits->map(fn ($l) => [
            'feature_key' => $l->feature_key,
            'limit_value' => $l->limit_value === null ? '' : $l->limit_value,
        ])->values()->all();
    } else {
        $limitRows = collect($limitRows)->map(fn ($r) => [
            'feature_key' => (string) ($r['feature_key'] ?? ''),
            'limit_value' => ($r['limit_value'] ?? '') === '' || ($r['limit_value'] ?? null) === null ? '' : $r['limit_value'],
        ])->all();
    }
    $availableFeatures = app(PlanLimitService::class)->featureCatalog();
    $featureStatusByKey = collect($planFeatureRows ?? [])->keyBy('key');
    $minLimitRows = count($availableFeatures) + 4;
    while (count($limitRows) < $minLimitRows) {
        $limitRows[] = ['feature_key' => '', 'limit_value' => ''];
    }
@endphp

<x-admin-layout :title="$plan->name">
    <div class="mb-6">
        <a
            href="{{ route('admin.plans.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Back to plans') }}</span>
        </a>
    </div>

    <div class="flow-panel max-w-xl p-8">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Edit plan') }}</h2>
        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $plan->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="slug" :value="__('Slug')" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full font-mono" :value="old('slug', $plan->slug)" required />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="price_monthly" :value="__('Price per month')" />
                <x-text-input id="price_monthly" name="price_monthly" type="number" class="mt-1 block w-full" :value="old('price_monthly',$plan->price_monthly)" required min="0" />
                <p class="mt-1 text-xs text-slate-500">{{ __('Enter major units only (e.g. 199 = 199.00).') }}</p>
                <x-input-error :messages="$errors->get('price_monthly')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <x-text-input id="currency" name="currency" class="mt-1 block w-full uppercase" maxlength="3" :value="old('currency', $plan->currency)" required />
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-600 dark:bg-slate-800/40">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Total price by billing period') }}</p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Enter the total amount charged for the full period in the plan currency (not per month).') }}</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="price_3m" :value="__('3 months')" />
                        <x-text-input id="price_3m" name="price_3m" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price_3m', $defaultPeriodMajor(3))" required />
                    </div>
                    <div>
                        <x-input-label for="price_6m" :value="__('6 months')" />
                        <x-text-input id="price_6m" name="price_6m" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price_6m', $defaultPeriodMajor(6))" required />
                    </div>
                    <div>
                        <x-input-label for="price_12m" :value="__('1 year')" />
                        <x-text-input id="price_12m" name="price_12m" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price_12m', $defaultPeriodMajor(12))" required />
                    </div>
                </div>
                <x-input-error :messages="$errors->get('price_3m')" class="mt-2" />
                <x-input-error :messages="$errors->get('price_6m')" class="mt-2" />
                <x-input-error :messages="$errors->get('price_12m')" class="mt-2" />
            </div>
            <x-primary-button type="submit">
                <span class="inline-flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                    <span>{{ __('Save') }}</span>
                </span>
            </x-primary-button>
        </form>
    </div>

    <div
        class="mt-10 grid gap-6 lg:grid-cols-12"
        x-data="{
            rows: @js($limitRows),
            addFeature(key) {
                const idx = this.rows.findIndex(r => (r.feature_key || '').trim() === '');
                const target = idx === -1 ? this.rows.length : idx;
                if (idx === -1) this.rows.push({ feature_key: '', limit_value: '' });
                this.rows[target].feature_key = key;
            },
            hasFeature(key) {
                return this.rows.some(r => (r.feature_key || '').trim() === key);
            }
        }"
    >
        <aside class="flow-panel lg:col-span-4 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Features') }}</h2>
                    <p class="mt-1 text-xs text-slate-600">{{ __('Click a feature to add it to this plan.') }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-2">
                @foreach ($availableFeatures as $f)
                    <button
                        type="button"
                        class="group flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="addFeature(@js($f['key']))"
                        :disabled="hasFeature(@js($f['key']))"
                    >
                        <span class="inline-flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200">
                                <i class="fa-solid {{ $f['icon'] }} text-sm" aria-hidden="true"></i>
                            </span>
                            <span class="truncate">
                                <span class="block">{{ $f['label'] }}</span>
                                <span class="block font-mono text-[11px] font-semibold text-slate-500">{{ $f['key'] }}</span>
                            </span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            @php($statusRow = $featureStatusByKey->get($f['key']))
                            @if ($statusRow && ! $statusRow['enabled'])
                                <span class="text-xs font-semibold text-rose-600">{{ __('Not included') }}</span>
                            @elseif ($statusRow)
                                <span class="text-xs font-semibold text-emerald-600">{{ $statusRow['status'] }}</span>
                            @endif
                            <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-500" x-show="!hasFeature(@js($f['key']))">+</span>
                            <span class="text-xs font-semibold text-emerald-600" x-show="hasFeature(@js($f['key']))">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                <div class="font-semibold text-slate-700">{{ __('Notes') }}</div>
                <ul class="mt-2 list-disc space-y-1 ps-4">
                    <li>{{ __('Leave limit blank for unlimited.') }}</li>
                    <li>{{ __('plan_limits_note_zero_disables') }}</li>
                    <li>{{ __('Limits are enforced in code when creating resources (e.g. projects, forms).') }}</li>
                </ul>
            </div>
        </aside>

        <section class="flow-panel lg:col-span-8 p-8">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Plan features & limits') }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('Set limits per feature. Leave limit empty for unlimited.') }}
            </p>

        @if ($errors->has('limits'))
            <p class="mt-4 text-sm text-rose-600">{{ $errors->first('limits') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.plans.limits.update', $plan) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <template x-for="(row, i) in rows" :key="i">
                <div class="grid gap-3 sm:grid-cols-12 sm:items-end">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-slate-500" x-bind:for="'fk_'+i">{{ __('Feature key') }}</label>
                        <input type="text" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" x-model="row.feature_key" x-bind:name="'limits['+i+'][feature_key]'" x-bind:id="'fk_'+i" />
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-slate-500" x-bind:for="'lv_'+i">{{ __('Limit (blank = unlimited)') }}</label>
                        <input type="number" min="0" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" x-model="row.limit_value" x-bind:name="'limits['+i+'][limit_value]'" x-bind:id="'lv_'+i" />
                    </div>
                    <div class="sm:col-span-3 flex items-end">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 hover:text-rose-800"
                            @click="rows.splice(i, 1)"
                            x-show="rows.length > 1"
                        >
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            <span>{{ __('Remove') }}</span>
                        </button>
                    </div>
                </div>
            </template>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                @click="rows.push({ feature_key: '', limit_value: '' })"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>{{ __('Add feature row') }}</span>
            </button>

            <div>
                <x-primary-button type="submit" class="mt-4">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                        <span>{{ __('Save features') }}</span>
                    </span>
                </x-primary-button>
            </div>
        </form>
        </section>
    </div>
</x-admin-layout>
