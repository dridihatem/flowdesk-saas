<?php

namespace App\Services;

use App\Enums\MarketplaceOrderStatus;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class MarketplaceCheckoutService
{
    public function __construct(
        private MarketplaceCartService $cart,
        private InvoicePaymentGatewayService $gateways,
    ) {}

    public function stripeReady(): bool
    {
        $creds = $this->gateways->platformCredentials();

        return ! empty($creds['stripe_secret_key']) && ! empty($creds['stripe_public_key']);
    }

    /**
     * @param  array{name: string, email: string, company?: string|null}  $customer
     */
    public function createOrder(array $customer, ?User $user = null): MarketplaceOrder
    {
        if ($this->cart->isEmpty()) {
            throw new RuntimeException(__('marketing_cart_empty'));
        }

        $currency = $this->cart->currency();
        $lines = $this->cart->lineItems();
        $total = $this->cart->totalMinor();

        return DB::transaction(function () use ($customer, $user, $currency, $lines, $total) {
            $orderNumber = $this->generateOrderNumber();

            $order = MarketplaceOrder::query()->create([
                'order_number' => $orderNumber,
                'payment_reference' => $orderNumber,
                'status' => MarketplaceOrderStatus::Pending,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_company' => $customer['company'] ?? null,
                'user_id' => $user?->id,
                'company_id' => $user?->company_id,
                'total_minor' => $total,
                'currency' => $currency,
            ]);

            foreach ($lines as $line) {
                $module = $line['module'];
                MarketplaceOrderItem::query()->create([
                    'marketplace_order_id' => $order->id,
                    'marketplace_module_id' => $module->id,
                    'module_slug' => $module->slug,
                    'module_name' => $module->name,
                    'price_minor' => $line['price_minor'],
                    'currency' => $currency,
                    'billing_period' => $module->billing_period->value,
                ]);
            }

            return $order->load('items.module');
        });
    }

    public function createStripeCheckoutSession(MarketplaceOrder $order): string
    {
        $secret = $this->gateways->platformCredentials()['stripe_secret_key'] ?? null;
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException(__('Stripe is not configured.'));
        }

        Stripe::setApiKey($secret);

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) $item->price_minor,
                    'product_data' => [
                        'name' => $item->module_name,
                        'description' => __('marketplace_module_billing.'.$item->billing_period),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->customer_email,
            'success_url' => route('marketing.checkout.success', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('marketing.checkout.cancel', ['order' => $order->id], true),
            'metadata' => [
                'marketplace_order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return (string) $session->url;
    }

    public function completeFromStripeSession(string $sessionId): ?MarketplaceOrder
    {
        $secret = $this->gateways->platformCredentials()['stripe_secret_key'] ?? null;
        if (! is_string($secret) || $secret === '') {
            return null;
        }

        Stripe::setApiKey($secret);
        $session = StripeCheckoutSession::retrieve($sessionId);

        $orderId = $session->metadata->marketplace_order_id ?? null;
        if (! is_string($orderId) || $orderId === '') {
            return null;
        }

        $order = MarketplaceOrder::query()->with('items.module')->find($orderId);
        if (! $order || $order->isPaid()) {
            return $order;
        }

        if ($session->payment_status === 'paid') {
            $this->markPaid($order, (string) ($session->payment_intent ?? ''));
        }

        return $order;
    }

    public function markPaid(MarketplaceOrder $order, ?string $paymentIntentId = null): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->update([
            'status' => MarketplaceOrderStatus::Paid,
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);

        $this->cart->clear();

        app(MarketplaceOrderFulfillmentService::class)->fulfill($order->fresh());
    }

    public function cancelOrder(MarketplaceOrder $order): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->update(['status' => MarketplaceOrderStatus::Cancelled]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'MK-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (MarketplaceOrder::query()->where('order_number', $number)->exists());

        return $number;
    }
}
