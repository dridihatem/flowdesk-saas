<?php

namespace App\Services;

use App\Enums\PaymentEntryKind;
use App\Enums\PaymentStatus;
use App\Enums\RemittanceMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;

class InvoiceOnlinePaymentService
{
    public function recordCompletedPayment(
        Invoice $invoice,
        int $amountMinor,
        RemittanceMethod $method,
        string $provider,
        string $externalId,
        ?array $meta = null,
    ): ?Payment {
        if ($amountMinor < 1) {
            return null;
        }

        if (Payment::query()->withoutGlobalScopes()->where('external_id', $externalId)->exists()) {
            return null;
        }

        $payment = Payment::query()->withoutGlobalScopes()->create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'amount' => $amountMinor,
            'currency' => $invoice->currency,
            'status' => PaymentStatus::Completed,
            'payment_kind' => PaymentEntryKind::Standard,
            'payment_method' => $method,
            'provider' => $provider,
            'external_id' => $externalId,
            'paid_at' => now(),
        ]);

        Transaction::query()->withoutGlobalScopes()->create([
            'company_id' => $invoice->company_id,
            'payment_id' => $payment->id,
            'type' => $provider.'_payment',
            'amount' => $amountMinor,
            'currency' => $invoice->currency,
            'status' => 'completed',
            'meta' => $meta,
        ]);

        $invoice->refresh();
        $invoice->syncStatusWithPayments();

        return $payment;
    }
}
