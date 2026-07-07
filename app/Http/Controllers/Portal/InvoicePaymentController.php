<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentEntryKind;
use App\Enums\PaymentStatus;
use App\Enums\RemittanceMethod;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesPortalClient;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceOnlinePaymentService;
use App\Services\InvoicePaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class InvoicePaymentController extends Controller
{
    use ResolvesPortalClient;

    public function __construct(
        private InvoicePaymentGatewayService $gateways,
        private InvoiceOnlinePaymentService $onlinePayments,
    ) {}

    public function stripeIntent(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);
        $this->assertGateway($invoice, InvoicePaymentGatewayService::GATEWAY_STRIPE);

        $balance = $this->balanceMinor($invoice);
        if ($balance < 1) {
            return response()->json(['message' => __('This invoice is already paid.')], 422);
        }

        $creds = flowdesk_invoice_payment_credentials($invoice->company);
        $secret = $creds['stripe_secret_key'] ?? null;
        if ($secret === null || $secret === '') {
            return response()->json(['message' => __('Stripe is not configured.')], 422);
        }

        Stripe::setApiKey($secret);
        $intent = PaymentIntent::create([
            'amount' => $balance,
            'currency' => strtolower((string) $invoice->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_portal' => '1',
            ],
        ]);

        return response()->json(['client_secret' => $intent->client_secret]);
    }

    public function paypalOrder(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);
        $this->assertGateway($invoice, InvoicePaymentGatewayService::GATEWAY_PAYPAL);

        $balance = $this->balanceMinor($invoice);
        if ($balance < 1) {
            return response()->json(['message' => __('This invoice is already paid.')], 422);
        }

        $creds = flowdesk_invoice_payment_credentials($invoice->company);
        $token = $this->paypalAccessToken($creds);
        if ($token === null) {
            return response()->json(['message' => __('PayPal is not configured.')], 422);
        }

        $ic = flowdesk_invoice_currency($invoice);
        $decimals = flowdesk_currency_fraction_digits($ic);
        $amount = number_format(flowdesk_minor_to_major($balance, $ic), $decimals, '.', '');
        $base = $this->paypalApiBase($creds);

        $orderRes = Http::withToken($token)
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
                    'return_url' => url(route('portal.invoices.paypal.return', $invoice, false)),
                    'cancel_url' => url(route('portal.invoices.show', $invoice, false).'?payment=cancelled'),
                    'brand_name' => $invoice->company?->name ?? config('app.name'),
                ],
            ]);

        if (! $orderRes->successful()) {
            return response()->json(['message' => __('PayPal order creation failed.')], 422);
        }

        $links = $orderRes->json('links', []);
        $approve = collect($links)->firstWhere('rel', 'approve');

        return response()->json([
            'order_id' => $orderRes->json('id'),
            'approval_url' => $approve['href'] ?? null,
        ]);
    }

    public function paypalReturn(Request $request, Invoice $invoice): RedirectResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);

        $token = $request->query('token');
        if (! $token) {
            return redirect()
                ->route('portal.invoices.show', $invoice)
                ->withErrors(['payment' => __('PayPal payment was cancelled.')]);
        }

        $creds = flowdesk_invoice_payment_credentials($invoice->company);
        $accessToken = $this->paypalAccessToken($creds);
        if ($accessToken === null) {
            return redirect()
                ->route('portal.invoices.show', $invoice)
                ->withErrors(['payment' => __('PayPal is not configured.')]);
        }

        $base = $this->paypalApiBase($creds);
        $captureRes = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($base.'/v2/checkout/orders/'.$token.'/capture');

        if (! $captureRes->successful()) {
            return redirect()
                ->route('portal.invoices.show', $invoice)
                ->withErrors(['payment' => __('PayPal capture failed.')]);
        }

        $capture = collect($captureRes->json('purchase_units.0.payments.captures', []))->first();
        $amountMajor = (float) data_get($capture, 'amount.value', 0);
        $ic = flowdesk_invoice_currency($invoice);
        $amountMinor = flowdesk_decimal_to_minor(number_format($amountMajor, flowdesk_currency_fraction_digits($ic), '.', ''), $ic)
            ?? $this->balanceMinor($invoice);

        $this->onlinePayments->recordCompletedPayment(
            $invoice,
            (int) $amountMinor,
            RemittanceMethod::PayPal,
            'paypal',
            'paypal:'.(data_get($capture, 'id') ?? $token),
            ['paypal_order_id' => $token, 'capture' => $captureRes->json()],
        );

        return redirect()
            ->route('portal.invoices.show', $invoice)
            ->with('status', __('portal_payment_success'));
    }

    public function flouciPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);
        $this->assertGateway($invoice, InvoicePaymentGatewayService::GATEWAY_FLOUCI);

        $balance = $this->balanceMinor($invoice);
        if ($balance < 1) {
            return response()->json(['message' => __('This invoice is already paid.')], 422);
        }

        $creds = flowdesk_invoice_payment_credentials($invoice->company);
        $public = $creds['flouci_public_key'] ?? null;
        $private = $creds['flouci_secret_key'] ?? null;

        if (! $public || ! $private) {
            return response()->json(['message' => __('Flouci is not configured.')], 422);
        }

        $base = $creds['flouci_api_base'] ?? 'https://developers.flouci.com/api/v2/generate_payment';
        $successUrl = url(route('portal.invoices.show', $invoice, false).'?payment=pending&provider=flouci');
        $failUrl = url(route('portal.invoices.show', $invoice, false).'?payment=cancelled');

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$public.':'.$private,
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post($base, [
                'amount' => $balance,
                'developer_tracking_id' => (string) $invoice->id,
                'success_link' => $successUrl,
                'fail_link' => $failUrl,
                'webhook' => route('webhooks.flouci', absolute: true),
                'client_id' => $client->email ?? $client->name ?? (string) $invoice->id,
                'accept_card' => true,
            ]);

        if (! $res->successful()) {
            return response()->json(['message' => __('Flouci payment link failed.')], 422);
        }

        return response()->json([
            'payment_url' => data_get($res->json(), 'result.link'),
            'payment_id' => data_get($res->json(), 'result.payment_id'),
        ]);
    }

    public function bankTransfer(Request $request, Invoice $invoice): RedirectResponse
    {
        $client = $this->portalClient($request);
        $this->authorizePortalInvoice($client, $invoice);
        $this->assertGateway($invoice, InvoicePaymentGatewayService::GATEWAY_BANK);

        $balance = $this->balanceMinor($invoice);
        if ($balance < 1) {
            return redirect()
                ->route('portal.invoices.show', $invoice)
                ->withErrors(['payment' => __('This invoice is already paid.')]);
        }

        $ic = flowdesk_invoice_currency($invoice);
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:32'],
            'client_notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $amountMinor = flowdesk_decimal_to_minor((string) $data['amount'], $ic);
        if ($amountMinor === null || $amountMinor < 1) {
            throw ValidationException::withMessages([
                'amount' => __('Enter a positive amount in the invoice currency.'),
            ]);
        }
        if ($amountMinor > $balance) {
            throw ValidationException::withMessages([
                'amount' => __('Amount cannot exceed the balance due.'),
            ]);
        }

        $path = $request->file('receipt')->store('payment-receipts/'.$invoice->company_id, 'local');

        Payment::query()->withoutGlobalScopes()->create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'amount' => $amountMinor,
            'currency' => $invoice->currency,
            'status' => PaymentStatus::Pending,
            'payment_kind' => PaymentEntryKind::Standard,
            'payment_method' => RemittanceMethod::BankTransfer,
            'provider' => 'client_portal',
            'external_id' => null,
            'receipt_path' => $path,
            'client_notes' => $data['client_notes'] ?? null,
        ]);

        return redirect()
            ->route('portal.invoices.show', $invoice)
            ->with('status', __('portal_bank_transfer_submitted'));
    }

    private function assertGateway(Invoice $invoice, string $gatewayId): void
    {
        $company = $invoice->company;
        abort_if(! $company, 403);
        abort_if(! $this->gateways->isGatewayEnabled($company, $gatewayId), 403);
    }

    private function balanceMinor(Invoice $invoice): int
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return 0;
        }

        return max(0, (int) $invoice->amount - $invoice->completedPaymentsTotalMinor());
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    private function paypalAccessToken(array $creds): ?string
    {
        $clientId = $creds['paypal_client_id'] ?? null;
        $secret = $creds['paypal_secret'] ?? null;
        if (! $clientId || ! $secret) {
            return null;
        }

        $tokenRes = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->timeout(30)
            ->post($this->paypalApiBase($creds).'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        return $tokenRes->successful() ? $tokenRes->json('access_token') : null;
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    private function paypalApiBase(array $creds): string
    {
        return ($creds['paypal_mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
