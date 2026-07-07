<?php

namespace App\Services;

use App\Models\MarketplaceModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MarketingRegionService
{
    /**
     * @return array<string, array{label_key: string, currency: string, countries?: list<string>}>
     */
    public function regions(): array
    {
        $regions = config('flowdesk.marketing_regions', []);

        return is_array($regions) ? $regions : [];
    }

    /**
     * @return array{region: string, country: string|null, currency: string, regionConfig: array<string, mixed>}
     */
    public function resolve(Request $request): array
    {
        $regions = $this->regions();
        $region = (string) $request->query('region', 'global');
        if (! isset($regions[$region])) {
            $region = 'global';
        }

        $regionConfig = $regions[$region];
        $allowedCountries = $this->countriesForRegion($region);
        $country = strtoupper(trim((string) $request->query('country', '')));
        if ($country !== '' && ! in_array($country, $allowedCountries, true)) {
            $country = '';
        }

        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        if ($region === 'global') {
            $currency = strtoupper((string) $request->query('currency', 'USD'));
            if (! in_array($currency, $supported, true)) {
                $currency = 'USD';
            }
        } else {
            $currency = strtoupper((string) ($regionConfig['currency'] ?? 'USD'));
            if (! in_array($currency, $supported, true)) {
                $currency = 'USD';
            }
        }

        return [
            'region' => $region,
            'country' => $country !== '' ? $country : null,
            'currency' => $currency,
            'regionConfig' => $regionConfig,
        ];
    }

    /**
     * @return list<string>
     */
    public function countriesForRegion(string $region): array
    {
        $regions = $this->regions();
        if (! isset($regions[$region])) {
            return [];
        }

        $countries = $regions[$region]['countries'] ?? null;
        if ($region === 'global') {
            $codes = config('flowdesk.marketplace_module_countries', []);
            $codes = is_array($codes) ? $codes : [];

            return array_values(array_filter(array_map(
                fn ($c) => strtoupper(trim((string) $c)),
                $codes,
            ), fn (string $c) => strlen($c) === 2));
        }

        if (! is_array($countries)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($c) => strtoupper(trim((string) $c)),
            $countries,
            array_keys($countries),
        ), fn (string $c) => strlen($c) === 2));
    }

    /**
     * @return array<string, string> ISO code => English name
     */
    public function countryNames(): array
    {
        $names = config('flowdesk_countries', []);

        return is_array($names) ? $names : [];
    }

    /**
     * @return array<string, string>
     */
    public function countryOptionsForRegion(string $region): array
    {
        $names = $this->countryNames();
        $codes = $this->countriesForRegion($region);
        $out = [];
        foreach ($codes as $code) {
            $out[$code] = $names[$code] ?? $code;
        }
        asort($out);

        return $out;
    }

    public function publishedModulesQuery(?string $country, ?array $regionCountries = null): Builder
    {
        $query = MarketplaceModule::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($country !== null && $country !== '') {
            $country = strtoupper($country);

            return $query->where(function (Builder $q) use ($country): void {
                $q->whereNull('target_countries')
                    ->orWhereJsonLength('target_countries', 0)
                    ->orWhereJsonContains('target_countries', $country);
            });
        }

        if ($regionCountries !== null && $regionCountries !== []) {
            $regionCountries = array_values(array_unique(array_map('strtoupper', $regionCountries)));

            return $query->where(function (Builder $q) use ($regionCountries): void {
                $q->whereNull('target_countries')
                    ->orWhereJsonLength('target_countries', 0);

                foreach ($regionCountries as $iso) {
                    $q->orWhereJsonContains('target_countries', $iso);
                }
            });
        }

        return $query;
    }

    public function regionLabel(string $region): string
    {
        $regions = $this->regions();
        $key = $regions[$region]['label_key'] ?? null;

        return is_string($key) && $key !== '' ? __($key) : ucfirst(str_replace('_', ' ', $region));
    }
}
