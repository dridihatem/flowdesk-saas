<?php

namespace App\Enums;

enum ProjectInstallmentPaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Paypal = 'paypal';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => __('Installment method bank transfer'),
            self::Card => __('Installment method card'),
            self::Paypal => __('Installment method PayPal'),
            self::Cash => __('Installment method cash'),
            self::Other => __('Installment method other'),
        };
    }
}
