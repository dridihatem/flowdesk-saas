<?php

namespace App\Services;

use App\Models\Company;

class InvoiceTotalsService
{
    /**
     * @return array{subtotal: int, vat: int, stamp: int, total: int}
     */
    public function fromSubtotalMinor(int $subtotalMinor, Company $company): array
    {
        $company->loadMissing('settings');
        $billing = $company->settings?->billing;
        $billing = is_array($billing) ? $billing : [];

        $vatPct = isset($billing['vat_percent']) ? (float) $billing['vat_percent'] : 0.0;
        $stampEnabled = filter_var($billing['fiscal_stamp_enabled'] ?? false, FILTER_VALIDATE_BOOL);
        $stampMinor = $stampEnabled && isset($billing['fiscal_stamp_minor'])
            ? max(0, (int) $billing['fiscal_stamp_minor'])
            : 0;

        $vat = $vatPct > 0 ? (int) round($subtotalMinor * $vatPct / 100.0) : 0;

        return [
            'subtotal' => $subtotalMinor,
            'vat' => $vat,
            'stamp' => $stampMinor,
            'total' => $subtotalMinor + $vat + $stampMinor,
        ];
    }
}
