<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\RemittanceMethod;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\MarketplaceOrder;
use App\Services\InvoiceOnlinePaymentService;
use App\Services\MarketplaceCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, InvoiceOnlinePaymentService $onlinePayments): Response
    {
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if ($secret === null || $secret === '' || $sig === null) {
            return response('Webhook not configured', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->marketplace_order_id ?? null;
            if (is_string($orderId) && $orderId !== '' && ($session->payment_status ?? '') === 'paid') {
                $order = MarketplaceOrder::query()->find($orderId);
                if ($order && ! $order->isPaid()) {
                    app(MarketplaceCheckoutService::class)->markPaid(
                        $order,
                        is_string($session->payment_intent ?? null) ? $session->payment_intent : null,
                    );
                }
            }
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $companyId = $intent->metadata->company_id ?? null;
            $invoiceId = $intent->metadata->invoice_id ?? null;
            if ($companyId && $invoiceId) {
                $company = Company::query()->find($companyId);
                $invoice = Invoice::query()->withoutGlobalScope('tenant')->where('company_id', $companyId)->where('id', $invoiceId)->first();
                if ($company && $invoice) {
                    $onlinePayments->recordCompletedPayment(
                        $invoice,
                        (int) $intent->amount_received,
                        RemittanceMethod::Stripe,
                        'stripe',
                        (string) $intent->id,
                        ['stripe_event_id' => $event->id],
                    );
                }
            }
        }

        return response('OK', 200);
    }
}
