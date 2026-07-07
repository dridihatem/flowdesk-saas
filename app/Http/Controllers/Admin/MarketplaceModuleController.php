<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarketplaceModuleBillingPeriod;
use App\Enums\MarketplaceModuleCategory;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceModule;
use App\Services\MarketplaceModuleMediaService;
use App\Services\MarketplaceModuleZipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class MarketplaceModuleController extends Controller
{
    public function index(): View
    {
        $modules = MarketplaceModule::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.marketplace-modules.index', compact('modules'));
    }

    public function create(): View
    {
        return view('admin.marketplace-modules.create', $this->formOptions());
    }

    public function store(Request $request, MarketplaceModuleZipService $zipService, MarketplaceModuleMediaService $mediaService): RedirectResponse
    {
        $module = MarketplaceModule::query()->create($this->validated($request));

        $mediaResult = $this->applyMediaUploads($request, $module, $mediaService);
        if ($mediaResult instanceof RedirectResponse) {
            $zipService->delete($module);
            $mediaService->deleteAll($module);
            $module->delete();

            return $mediaResult;
        }

        if ($request->hasFile('module_zip')) {
            try {
                $path = $zipService->store($module, $request->file('module_zip'));
                $module->update(['zip_path' => $path]);
            } catch (RuntimeException $e) {
                $mediaService->deleteAll($module);
                $zipService->delete($module);
                $module->delete();

                return back()
                    ->withErrors(['module_zip' => $e->getMessage()])
                    ->withInput();
            }
        }

        return redirect()
            ->route('admin.marketplace-modules.index')
            ->with('status', __('admin_marketplace_module_saved'));
    }

    public function edit(MarketplaceModule $marketplaceModule): View
    {
        return view('admin.marketplace-modules.edit', array_merge(
            $this->formOptions(),
            ['module' => $marketplaceModule],
        ));
    }

    public function update(Request $request, MarketplaceModule $marketplaceModule, MarketplaceModuleZipService $zipService, MarketplaceModuleMediaService $mediaService): RedirectResponse
    {
        $data = $this->validated($request, $marketplaceModule->id);

        if ($request->boolean('remove_zip')) {
            $zipService->delete($marketplaceModule);
            $data['zip_path'] = null;
        }

        if ($request->hasFile('module_zip')) {
            try {
                $data['zip_path'] = $zipService->store($marketplaceModule, $request->file('module_zip'));
            } catch (RuntimeException $e) {
                return back()
                    ->withErrors(['module_zip' => $e->getMessage()])
                    ->withInput();
            }
        }

        $mediaResult = $this->applyMediaUploads($request, $marketplaceModule, $mediaService);
        if ($mediaResult instanceof RedirectResponse) {
            return $mediaResult;
        }
        $data = array_merge($data, $mediaResult ?? []);

        $marketplaceModule->update($data);

        return redirect()
            ->route('admin.marketplace-modules.index')
            ->with('status', __('admin_marketplace_module_saved'));
    }

    public function destroy(MarketplaceModule $marketplaceModule, MarketplaceModuleZipService $zipService, MarketplaceModuleMediaService $mediaService): RedirectResponse
    {
        $zipService->delete($marketplaceModule);
        $mediaService->deleteAll($marketplaceModule);
        $marketplaceModule->delete();

        return redirect()
            ->route('admin.marketplace-modules.index')
            ->with('status', __('admin_marketplace_module_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $currencies = config('flowdesk.supported_currencies', ['USD']);

        return [
            'categories' => MarketplaceModuleCategory::orderedCases(),
            'billingPeriods' => MarketplaceModuleBillingPeriod::cases(),
            'currencies' => is_array($currencies) ? $currencies : ['USD'],
            'currencyLabels' => config('flowdesk.currency_labels', []),
            'countryOptions' => $this->marketplaceCountryOptions(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function marketplaceCountryOptions(): array
    {
        $codes = config('flowdesk.marketplace_module_countries', []);
        $codes = is_array($codes) ? $codes : [];
        $names = config('flowdesk_countries', []);
        $names = is_array($names) ? $names : [];
        $out = [];
        foreach ($codes as $code) {
            $code = strtoupper(trim((string) $code));
            if (strlen($code) === 2) {
                $out[$code] = $names[$code] ?? $code;
            }
        }
        asort($out);

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $ignoreId = null): array
    {
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9][a-z0-9_-]{1,63}$/',
                Rule::unique('marketplace_modules', 'slug')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'detail_content' => ['nullable', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(MarketplaceModuleCategory::class)],
            'price' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'size:3', Rule::in(config('flowdesk.supported_currencies', ['USD']))],
            'billing_period' => ['required', Rule::enum(MarketplaceModuleBillingPeriod::class)],
            'icon' => ['nullable', 'string', 'max:32'],
            'feature_bullets' => ['nullable', 'string', 'max:8000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
            'module_zip' => ['nullable', 'file', 'mimes:zip', 'max:15360'],
            'remove_zip' => ['sometimes', 'boolean'],
            'module_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'module_cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_image' => ['sometimes', 'boolean'],
            'remove_cover' => ['sometimes', 'boolean'],
            'target_countries' => ['nullable', 'array'],
            'target_countries.*' => ['string', 'size:2'],
        ]);

        $currency = strtoupper($data['currency']);
        $priceMinor = flowdesk_decimal_to_minor((string) $data['price'], $currency);
        if ($priceMinor === null || $priceMinor < 0) {
            throw ValidationException::withMessages([
                'price' => [__('validation.numeric', ['attribute' => __('Price')])],
            ]);
        }

        $bullets = collect(preg_split('/\r\n|\r|\n/', (string) ($data['feature_bullets'] ?? '')) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $allowedCountries = array_keys($this->marketplaceCountryOptions());
        $targetCountries = collect($request->input('target_countries', []))
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter(fn (string $c) => strlen($c) === 2 && in_array($c, $allowedCountries, true))
            ->unique()
            ->values()
            ->all();

        return [
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'detail_content' => $data['detail_content'] ?? null,
            'category' => $data['category'],
            'target_countries' => $targetCountries === [] ? null : $targetCountries,
            'price_minor' => $priceMinor,
            'currency' => $currency,
            'billing_period' => $data['billing_period'],
            'icon' => $data['icon'] ?? null,
            'feature_bullets' => $bullets === [] ? null : $bullets,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    /**
     * @return array<string, mixed>|RedirectResponse|null
     */
    private function applyMediaUploads(Request $request, MarketplaceModule $module, MarketplaceModuleMediaService $mediaService): array|RedirectResponse|null
    {
        $updates = [];

        try {
            if ($request->boolean('remove_image')) {
                $mediaService->deleteImage($module);
                $updates['image_path'] = null;
            }
            if ($request->boolean('remove_cover')) {
                $mediaService->deleteCover($module);
                $updates['cover_path'] = null;
            }
            if ($request->hasFile('module_image')) {
                $updates['image_path'] = $mediaService->storeImage($module, $request->file('module_image'), 'image');
            }
            if ($request->hasFile('module_cover')) {
                $updates['cover_path'] = $mediaService->storeImage($module, $request->file('module_cover'), 'cover');
            }
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors(['module_image' => $e->getMessage()])->withInput();
        }

        if ($updates !== []) {
            $module->update($updates);
        }

        return $updates;
    }
}
