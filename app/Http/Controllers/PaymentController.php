<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentEntryKind;
use App\Enums\PaymentStatus;
use App\Enums\RemittanceMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        });
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if((string) $invoice->company_id !== (string) $company->id, 403);
        abort_if($invoice->status === InvoiceStatus::Cancelled, 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_kind' => ['required', Rule::enum(PaymentEntryKind::class)],
            'payment_method' => ['required', Rule::enum(RemittanceMethod::class)],
        ]);

        $amountMinor = flowdesk_decimal_to_minor((string) $data['amount'], flowdesk_invoice_currency($invoice));
        if ($amountMinor === null || $amountMinor < 1) {
            throw ValidationException::withMessages([
                'amount' => __('Enter a positive amount in the invoice currency.'),
            ]);
        }

        $status = $data['status'] instanceof PaymentStatus
            ? $data['status']
            : PaymentStatus::from((string) $data['status']);
        $kind = $data['payment_kind'] instanceof PaymentEntryKind
            ? $data['payment_kind']
            : PaymentEntryKind::from((string) $data['payment_kind']);
        $method = $data['payment_method'] instanceof RemittanceMethod
            ? $data['payment_method']
            : RemittanceMethod::from((string) $data['payment_method']);

        $paidAt = isset($data['paid_at']) && $data['paid_at'] !== ''
            ? Carbon::parse((string) $data['paid_at'])->startOfDay()
            : now();

        $payment = Payment::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'amount' => $amountMinor,
            'currency' => $invoice->currency,
            'status' => $status,
            'payment_kind' => $kind,
            'payment_method' => $method,
            'paid_at' => $paidAt,
            'provider' => 'advance',
            'external_id' => null,
        ]);

        if ($status === PaymentStatus::Completed) {
            Transaction::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'payment_id' => $payment->id,
                'type' => 'payment_received',
                'amount' => $amountMinor,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'meta' => ['notes' => $data['notes'] ?? null],
            ]);
        }

        $invoice->refresh();
        $invoice->syncStatusWithPayments();

        return redirect()->route('invoices.show', $invoice)->with('status', __('Payment recorded.'));
    }

    public function update(Request $request, Invoice $invoice, Payment $payment): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if((string) $invoice->company_id !== (string) $company->id, 403);
        abort_if((string) $payment->company_id !== (string) $company->id, 403);
        abort_if((string) $payment->invoice_id !== (string) $invoice->id, 404);

        $data = $request->validate([
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_kind' => ['required', Rule::enum(PaymentEntryKind::class)],
            'payment_method' => ['required', Rule::enum(RemittanceMethod::class)],
        ]);

        $status = $data['status'] instanceof PaymentStatus
            ? $data['status']
            : PaymentStatus::from((string) $data['status']);
        $kind = $data['payment_kind'] instanceof PaymentEntryKind
            ? $data['payment_kind']
            : PaymentEntryKind::from((string) $data['payment_kind']);
        $method = $data['payment_method'] instanceof RemittanceMethod
            ? $data['payment_method']
            : RemittanceMethod::from((string) $data['payment_method']);

        $paidAt = isset($data['paid_at']) && $data['paid_at'] !== ''
            ? Carbon::parse((string) $data['paid_at'])->startOfDay()
            : $payment->paid_at;

        $payment->update([
            'paid_at' => $paidAt,
            'status' => $status,
            'payment_kind' => $kind,
            'payment_method' => $method,
        ]);

        $invoice->refresh();
        $invoice->syncStatusWithPayments();

        return redirect()->route('invoices.show', $invoice)->with('status', __('Payment status updated.'));
    }
}
