<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InvoiceFlouciController extends Controller
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
        $public = $creds['flouci_public_key'] ?? null;
        $private = $creds['flouci_secret_key'] ?? null;

        if (! $public || ! $private) {
            return response()->json(['message' => __('Flouci is not configured.')], 422);
        }

        $base = $creds['flouci_api_base'] ?? 'https://developers.flouci.com/api/v2/generate_payment';

        $successUrl = route('invoices.show', $invoice, false).'?flouci=success';
        $failUrl = route('invoices.show', $invoice, false).'?flouci=fail';

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$public.':'.$private,
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post($base, [
                'amount' => (int) $invoice->amount,
                'developer_tracking_id' => (string) $invoice->id,
                'success_link' => url($successUrl),
                'fail_link' => url($failUrl),
                'webhook' => route('webhooks.flouci', absolute: true),
                'client_id' => $invoice->client?->email ?? $invoice->client?->name ?? (string) $invoice->id,
                'accept_card' => true,
            ]);

        if (! $res->successful()) {
            return response()->json(['message' => __('Flouci payment link failed.'), 'detail' => $res->json()], 422);
        }

        $body = $res->json();

        return response()->json([
            'payment_id' => data_get($body, 'result.payment_id'),
            'link' => data_get($body, 'result.link'),
        ]);
    }
}
