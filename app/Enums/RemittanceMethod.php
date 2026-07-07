<?php

namespace App\Enums;

enum RemittanceMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    case Check = 'check';
    case PayPal = 'paypal';
    case Stripe = 'stripe';
    case Flouci = 'flouci';
    case Sepa = 'sepa';
    case Other = 'other';

    public function label(): string
    {
        return __('remittance_method.'.$this->value);
    }
}
