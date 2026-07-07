<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\PlanPeriodPrice;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->with(['limits', 'periodPrices'])->orderBy('name')->get();
        $planLimitService = app(PlanLimitService::class);
        $planFeatureSummaries = $plans->mapWithKeys(
            fn (Plan $plan) => [$plan->id => $planLimitService->summarizePlanFeatures($plan)]
        );

        return view('admin.plans.index', compact('plans', 'planFeatureSummaries'));
    }

    public function create(): View
    {
        $currencies = config('flowdesk.supported_currencies', ['USD']);
        $currencyLabels = config('flowdesk.currency_labels', []);

        return view('admin.plans.create', compact('currencies', 'currencyLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', Rule::unique('plans', 'slug')],
            // Base price in USD (major units) used for conversion
            'base_price_usd' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', Rule::in(config('flowdesk.supported_currencies', ['USD']))],
        ]);

        $currency = strtoupper($data['currency']);
        $baseUsdMajor = (int) $data['base_price_usd'];

        $rate = 1.0;
        if ($currency !== 'USD') {
            $rateRow = CurrencyRate::query()
                ->where('base_currency', 'USD')
                ->where('quote_currency', $currency)
                ->first();
            $rate = $rateRow ? (float) $rateRow->rate : 1.0;
        }

        $priceMinor = (int) round(($baseUsdMajor * $rate) * 100);

        $monthlyMajor = (int) round($baseUsdMajor * $rate);

        $plan = Plan::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'currency' => $currency,
            'price_monthly' => $monthlyMajor,
            'addons' => [],
        ]);

        $perMonthMinor = (int) ($monthlyMajor * 100);
        foreach ([3, 6, 12] as $m) {
            PlanPeriodPrice::query()->create([
                'plan_id' => $plan->id,
                'period_months' => $m,
                'price_minor' => $perMonthMinor * $m,
            ]);
        }

        return redirect()->route('admin.plans.edit', $plan)->with('status', __('Plan created.'));
    }

    public function edit(Plan $plan): View
    {
        $plan->load(['limits', 'periodPrices']);
        $planFeatureRows = app(PlanLimitService::class)->summarizePlanFeatures($plan);

        return view('admin.plans.edit', compact('plan', 'planFeatureRows'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', Rule::unique('plans', 'slug')->ignore($plan->id)],
            // Admin enters major units; we store as-is (major units).
            'price_monthly' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'price_3m' => ['required', 'numeric', 'min:0'],
            'price_6m' => ['required', 'numeric', 'min:0'],
            'price_12m' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($plan, $data): void {
            $plan->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'price_monthly' => (int) $data['price_monthly'],
                'currency' => strtoupper($data['currency']),
            ]);

            foreach ([3 => 'price_3m', 6 => 'price_6m', 12 => 'price_12m'] as $months => $field) {
                $major = (float) $data[$field];
                PlanPeriodPrice::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'period_months' => $months,
                    ],
                    [
                        'price_minor' => (int) round($major * 100),
                    ]
                );
            }
        });

        return redirect()->route('admin.plans.index')->with('status', __('Plan updated.'));
    }

    public function updateLimits(Request $request, Plan $plan): RedirectResponse
    {
        $sanitized = collect($request->input('limits', []))->map(function (array $row): array {
            $raw = $row['limit_value'] ?? null;
            $limitValue = ($raw === '' || $raw === null) ? null : (int) $raw;

            return [
                'feature_key' => trim((string) ($row['feature_key'] ?? '')),
                'limit_value' => $limitValue,
            ];
        })->values()->all();

        $request->merge(['limits' => $sanitized]);

        $data = $request->validate([
            'limits' => ['required', 'array'],
            'limits.*.feature_key' => ['nullable', 'string', 'max:64'],
            'limits.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $rows = collect($data['limits'])->filter(fn (array $row): bool => $row['feature_key'] !== '');

        if ($rows->isEmpty()) {
            return redirect()->back()->withErrors(['limits' => __('Add at least one feature with a key (e.g. projects, users).')])->withInput();
        }

        $keys = $rows->pluck('feature_key')->all();
        if (count($keys) !== count(array_unique($keys))) {
            return redirect()->back()->withErrors(['limits' => __('Each feature key must be unique.')])->withInput();
        }

        DB::transaction(function () use ($plan, $rows): void {
            $plan->limits()->delete();
            foreach ($rows as $row) {
                PlanLimit::query()->create([
                    'plan_id' => $plan->id,
                    'feature_key' => $row['feature_key'],
                    'limit_value' => $row['limit_value'],
                ]);
            }
        });

        return redirect()->route('admin.plans.edit', $plan)->with('status', __('Plan features updated.'));
    }
}
