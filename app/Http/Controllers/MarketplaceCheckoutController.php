<?php

namespace App\Http\Controllers;

use App\Enums\MarketplaceOrderStatus;
use App\Models\MarketplaceOrder;
use App\Services\InvoicePaymentGatewayService;
use App\Services\MarketplaceCartService;
use App\Services\MarketplaceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceCheckoutController extends Controller
{
    public function show(MarketplaceCartService $cart, MarketplaceCheckoutService $checkout): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()
                ->route('marketing.modules')
                ->with('status', __('marketing_cart_empty'));
        }

        $gateways = app(InvoicePaymentGatewayService::class);
        $paymentMethods = $gateways->marketplaceCheckoutMethods();

        return view('marketing.checkout', [
            'lineItems' => $cart->lineItems(),
            'totalMinor' => $cart->totalMinor(),
            'currency' => $cart->currency(),
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => old('payment_method', $paymentMethods[0]['value'] ?? 'bank'),
            'bankDetails' => $gateways->bankTransferDetails(),
            'bankInstructions' => $gateways->bankTransferInstructions(),
        ]);
    }

    public function store(Request $request, MarketplaceCartService $cart, MarketplaceCheckoutService $checkout): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('marketing.modules')->with('status', __('marketing_cart_empty'));
        }

        $gatewayService = app(InvoicePaymentGatewayService::class);
        $allowedMethods = collect($gatewayService->marketplaceCheckoutMethods())->pluck('value')->all();

        if ($allowedMethods === []) {
            return back()
                ->withErrors(['checkout' => __('marketing_checkout_no_payment_methods')])
                ->withInput();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'string', Rule::in($allowedMethods)],
        ]);

        try {
            $order = $checkout->createOrder([
                'name' => $data['name'],
                'email' => $data['email'],
                'company' => $data['company'] ?? null,
            ], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }

        if ($data['payment_method'] === 'bank') {
            $order->update([
                'metadata' => array_merge(is_array($order->metadata) ? $order->metadata : [], [
                    'payment_method' => 'bank',
                ]),
            ]);
        }

        if ($data['payment_method'] === 'stripe' && $checkout->stripeReady()) {
            try {
                $url = $checkout->createStripeCheckoutSession($order);

                return redirect()->away($url);
            } catch (RuntimeException $e) {
                return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
            }
        }

        $cart->clear();

        return redirect()
            ->route('marketing.checkout.pending', ['order' => $order])
            ->with('status', __('marketing_checkout_pending'));
    }

    public function success(Request $request, MarketplaceCheckoutService $checkout): View|RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->route('marketing.modules');
        }

        $order = $checkout->completeFromStripeSession($sessionId);
        if (! $order || ! $order->isPaid()) {
            return redirect()
                ->route('marketing.modules')
                ->with('status', __('marketing_checkout_processing'));
        }

        return view('marketing.checkout-success', [
            'order' => $order,
        ]);
    }

    public function cancel(MarketplaceOrder $order, MarketplaceCheckoutService $checkout): RedirectResponse
    {
        $checkout->cancelOrder($order);

        return redirect()
            ->route('marketing.cart')
            ->with('status', __('marketing_checkout_cancelled'));
    }

    public function pending(MarketplaceOrder $order): View|RedirectResponse
    {
        if ($order->status !== MarketplaceOrderStatus::Pending) {
            return redirect()->route('marketing.modules');
        }

        $gateways = app(InvoicePaymentGatewayService::class);

        return view('marketing.checkout-pending', [
            'order' => $order->load('items.module'),
            'bankInstructions' => $gateways->bankTransferInstructions(),
            'bankDetails' => $gateways->bankTransferDetails(),
        ]);
    }

    public function download(Request $request, MarketplaceOrder $order, string $module): StreamedResponse
    {
        abort_unless($order->isPaid(), 403);
        abort_unless($request->hasValidSignature(), 403);

        $item = $order->items()->where('marketplace_module_id', $module)->first();
        abort_if(! $item, 404);

        $marketplaceModule = $item->module;
        abort_if(! $marketplaceModule || ! $marketplaceModule->zip_path, 404);

        $absolute = storage_path('app/'.$marketplaceModule->zip_path);
        abort_unless(is_file($absolute), 404);

        $filename = $marketplaceModule->slug.'.zip';

        return response()->streamDownload(
            fn () => readfile($absolute),
            $filename,
            ['Content-Type' => 'application/zip'],
        );
    }
}
