<?php

namespace App\Services;

use App\Models\MarketplaceModule;
use Illuminate\Support\Collection;

class MarketplaceCartService
{
    private const SESSION_KEY = 'marketplace_cart';

    /**
     * @return array{currency: string, items: array<string, int>}
     */
    public function snapshot(): array
    {
        $cart = session(self::SESSION_KEY, []);
        $currency = strtoupper((string) ($cart['currency'] ?? 'USD'));
        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        if (! in_array($currency, $supported, true)) {
            $currency = 'USD';
        }

        $items = is_array($cart['items'] ?? null) ? $cart['items'] : [];
        $normalized = [];
        foreach ($items as $id => $qty) {
            if (is_string($id) && $id !== '' && (int) $qty > 0) {
                $normalized[$id] = 1;
            }
        }

        return [
            'currency' => $currency,
            'items' => $normalized,
        ];
    }

    public function count(): int
    {
        return count($this->snapshot()['items']);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function setCurrency(string $currency): void
    {
        $currency = strtoupper(trim($currency));
        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        if (! in_array($currency, $supported, true)) {
            return;
        }

        $cart = $this->snapshot();
        $cart['currency'] = $currency;
        session([self::SESSION_KEY => $cart]);
    }

    public function add(MarketplaceModule $module, ?string $currency = null): void
    {
        abort_unless($module->is_published, 404);

        $cart = $this->snapshot();
        if ($currency !== null) {
            $cart['currency'] = strtoupper($currency);
        }

        $cart['items'][(string) $module->id] = 1;
        session([self::SESSION_KEY => $cart]);
    }

    public function remove(string $moduleId): void
    {
        $cart = $this->snapshot();
        unset($cart['items'][$moduleId]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, array{module: MarketplaceModule, price_minor: int}>
     */
    public function lineItems(): Collection
    {
        $cart = $this->snapshot();
        if ($cart['items'] === []) {
            return collect();
        }

        $modules = MarketplaceModule::query()
            ->whereIn('id', array_keys($cart['items']))
            ->where('is_published', true)
            ->get()
            ->keyBy('id');

        return collect($cart['items'])
            ->map(function (int $qty, string $id) use ($modules, $cart) {
                $module = $modules->get($id);
                if (! $module) {
                    return null;
                }

                return [
                    'module' => $module,
                    'price_minor' => $module->displayPriceMinor($cart['currency']),
                ];
            })
            ->filter()
            ->values();
    }

    public function totalMinor(): int
    {
        return (int) $this->lineItems()->sum('price_minor');
    }

    public function currency(): string
    {
        return $this->snapshot()['currency'];
    }
}
