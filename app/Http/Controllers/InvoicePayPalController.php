<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InvoicePayPalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        });
    }

    public function __invoke(Request $request, Invoice $invoice): JsonResponse
    {
        $company = $request->user()->company;
        abort_if(! $company || (string) $invoice->company_id !== (string) $company->id, 403);

        $creds = flowdesk_invoice_payment_credentials($invoice->company);
        $clientId = $creds['paypal_client_id'] ?? null;
        $secret = $creds['paypal_secret'] ?? null;
        $mode = $creds['paypal_mode'] ?? 'sandbox';

        if (! $clientId || ! $secret) {
            return response()->json(['message' => __('PayPal is not configured.')], 422);
        }

        $base = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $tokenRes = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->timeout(30)
            ->post($base.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $tokenRes->successful()) {
            return response()->json(['message' => __('PayPal authentication failed.'), 'detail' => $tokenRes->json()], 422);
        }

        $accessToken = $tokenRes->json('access_token');
        $ic = flowdesk_invoice_currency($invoice);
        $decimals = flowdesk_currency_fraction_digits($ic);
        $amount = number_format(flowdesk_minor_to_major((int) $invoice->amount, $ic), $decimals, '.', '');

        $orderRes = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($base.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $invoice->id,
                    'description' => __('Invoice :id', ['id' => $invoice->number ?? $invoice->id]),
                    'amount' => [
                        'currency_code' => $invoice->currency,
                        'value' => $amount,
                    ],
                ]],
                'application_context' => [
                    'return_url' => url(route('invoices.show', $invoice, false)),
                    'cancel_url' => url(route('invoices.show', $invoice, false)),
                    'brand_name' => $company->name,
                ],
            ]);

        if (! $orderRes->successful()) {
            return response()->json(['message' => __('PayPal order creation failed.'), 'detail' => $orderRes->json()], 422);
        }

        $links = $orderRes->json('links', []);
        $approve = collect($links)->firstWhere('rel', 'approve');

        return response()->json([
            'order_id' => $orderRes->json('id'),
            'approval_url' => $approve['href'] ?? null,
        ]);
    }
}
