<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\RemittanceMethod;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceOnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FlouciWebhookController extends Controller
{
    public function __invoke(Request $request, InvoiceOnlinePaymentService $onlinePayments): Response
    {
        $payload = $request->all();

        $fid = data_get($payload, 'data.payment_id')
            ?? data_get($payload, 'result.payment_id')
            ?? data_get($payload, 'payment_id');

        $invoiceId = data_get($payload, 'data.developer_tracking_id')
            ?? data_get($payload, 'developer_tracking_id')
            ?? data_get($payload, 'data.invoice_id');

        if (! $fid || ! $invoiceId) {
            Log::info('flouci.webhook.incomplete', ['payload' => $payload]);

            return response('OK', 200);
        }

        $invoice = Invoice::query()->withoutGlobalScope('tenant')->where('id', $invoiceId)->first();
        if ($invoice === null) {
            return response('OK', 200);
        }

        $amount = (int) (data_get($payload, 'data.amount')
            ?? data_get($payload, 'amount')
            ?? $invoice->amount);

        $onlinePayments->recordCompletedPayment(
            $invoice,
            $amount,
            RemittanceMethod::Flouci,
            'flouci',
            'flouci:'.$fid,
            ['payload' => $payload],
        );

        return response('OK', 200);
    }
}
