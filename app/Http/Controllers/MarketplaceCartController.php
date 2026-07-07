<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceModule;
use App\Services\MarketplaceCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceCartController extends Controller
{
    public function index(MarketplaceCartService $cart): View
    {
        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        return view('marketing.cart', [
            'lineItems' => $cart->lineItems(),
            'totalMinor' => $cart->totalMinor(),
            'currency' => $cart->currency(),
            'supportedCurrencies' => $supported,
            'currencyLabels' => is_array(config('flowdesk.currency_labels')) ? config('flowdesk.currency_labels') : [],
        ]);
    }

    public function add(Request $request, MarketplaceModule $marketplaceModule, MarketplaceCartService $cart): RedirectResponse
    {
        abort_unless($marketplaceModule->is_published, 404);

        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];
        $currency = strtoupper((string) $request->input('currency', $cart->currency()));
        if (! in_array($currency, $supported, true)) {
            $currency = $cart->currency();
        }

        $cart->add($marketplaceModule, $currency);

        return redirect()
            ->route('marketing.cart')
            ->with('status', __('marketing_cart_added', ['name' => $marketplaceModule->name]));
    }

    public function remove(Request $request, MarketplaceCartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'string'],
        ]);

        $cart->remove($data['module_id']);

        return redirect()
            ->route('marketing.cart')
            ->with('status', __('marketing_cart_removed'));
    }

    public function updateCurrency(Request $request, MarketplaceCartService $cart): RedirectResponse
    {
        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        $data = $request->validate([
            'currency' => ['required', 'string', 'size:3', 'in:'.implode(',', $supported)],
        ]);

        $cart->setCurrency($data['currency']);

        return redirect()->route('marketing.cart');
    }
}
