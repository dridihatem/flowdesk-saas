<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class InvoicePaymentIntentController extends Controller
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
        $secret = $creds['stripe_secret_key'] ?? null;
        if ($secret === null || $secret === '') {
            return response()->json(['message' => __('Stripe is not configured.')], 422);
        }

        Stripe::setApiKey($secret);
        $intent = PaymentIntent::create([
            'amount' => $invoice->amount,
            'currency' => strtolower($invoice->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
            ],
        ]);

        return response()->json(['client_secret' => $intent->client_secret]);
    }
}
