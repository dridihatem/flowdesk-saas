<?php

namespace App\Models;

use App\Enums\MarketplaceModuleBillingPeriod;
use App\Enums\MarketplaceModuleCategory;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceModule extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'feature_bullets' => 'array',
            'target_countries' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'category' => MarketplaceModuleCategory::class,
            'billing_period' => MarketplaceModuleBillingPeriod::class,
        ];
    }

    public function displayPriceMinor(string $displayCurrency): int
    {
        $displayCurrency = strtoupper(trim($displayCurrency));
        $from = strtoupper((string) $this->currency);

        return app(CurrencyConversionService::class)->convertMinor(
            (int) $this->price_minor,
            $from,
            $displayCurrency,
        );
    }

    public function formattedDisplayPrice(string $displayCurrency): string
    {
        $displayCurrency = strtoupper(trim($displayCurrency));
        $minor = $this->displayPriceMinor($displayCurrency);

        return flowdesk_format_minor($minor, $displayCurrency).' '.$displayCurrency;
    }

    public function isAvailableInCountry(?string $country): bool
    {
        if ($country === null || $country === '') {
            return true;
        }

        $targets = $this->target_countries;
        if (! is_array($targets) || $targets === []) {
            return true;
        }

        return in_array(strtoupper($country), array_map('strtoupper', $targets), true);
    }

    public function imageUrl(): ?string
    {
        return $this->publicAssetUrl($this->image_path);
    }

    public function coverUrl(): ?string
    {
        return $this->publicAssetUrl($this->cover_path);
    }

    /**
     * @return list<string>
     */
    public function featureList(): array
    {
        $bullets = $this->feature_bullets;

        return is_array($bullets) ? array_values(array_filter($bullets, fn ($b) => is_string($b) && trim($b) !== '')) : [];
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
