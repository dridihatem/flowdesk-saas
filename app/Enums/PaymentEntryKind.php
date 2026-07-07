<?php

namespace App\Enums;

enum PaymentEntryKind: string
{
    /** Normal payment against the invoice. */
    case Standard = 'standard';
    /** Deposit / down payment (acompte). */
    case Deposit = 'deposit';
    /** Scheduled installment. */
    case Installment = 'installment';
    /** Refund or reversal. */
    case Refund = 'refund';

    public function label(): string
    {
        return __('payment_entry_kind.'.$this->value);
    }
}
