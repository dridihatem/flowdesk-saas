<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesPortalClient;
use App\Models\Invoice;
use App\Services\InvoicePaymentGatewayService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use ResolvesPortalClient;

    public function show(Request $request, Invoice $invoice, InvoicePaymentGatewayService $gateways): View
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);

        $invoice->load(['items', 'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('created_at'), 'company']);

        $completedTotal = $invoice->completedPaymentsTotalMinor();
        $balanceMinor = max(0, (int) $invoice->amount - $completedTotal);
        $company = $invoice->company;
        $paymentCreds = flowdesk_invoice_payment_credentials($company);
        $paymentMethods = $gateways->clientPaymentMethods($company);
        $canPay = $balanceMinor > 0
            && $invoice->status !== InvoiceStatus::Cancelled
            && $invoice->status !== InvoiceStatus::Paid
            && $paymentMethods !== [];

        $paymentBanner = match (true) {
            $request->query('payment') === 'success' => 'success',
            $request->query('payment') === 'pending' => 'pending',
            $request->query('payment') === 'cancelled', $request->query('flouci') === 'fail' => 'cancelled',
            $request->query('flouci') === 'success' => 'pending',
            default => null,
        };

        return view('portal.invoices.show', compact(
            'client',
            'invoice',
            'completedTotal',
            'balanceMinor',
            'paymentCreds',
            'paymentMethods',
            'canPay',
            'paymentBanner',
        ));
    }

    public function pdf(Request $request, Invoice $invoice, InvoicePdfService $pdfs): StreamedResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);

        return $pdfs->stream($invoice->loadMissing(['client', 'items', 'company']));
    }
}
