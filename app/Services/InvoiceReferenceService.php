<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;

class InvoiceReferenceService
{
    /**
     * @return array{prefix: string, pad: int}
     */
    public function referenceSettings(Company $company): array
    {
        $company->loadMissing('settings');
        $templates = is_array($company->settings?->document_templates) ? $company->settings->document_templates : [];
        $prefix = trim((string) ($templates['invoice_reference_prefix'] ?? 'INV'));
        if ($prefix === '') {
            $prefix = 'INV';
        }
        $pad = max(1, min(12, (int) ($templates['invoice_reference_pad'] ?? 3)));

        return ['prefix' => $prefix, 'pad' => $pad];
    }

    public function examplePreview(Company $company): string
    {
        $year = (int) now()->format('Y');
        $s = $this->referenceSettings($company);

        return $s['prefix'].'-'.str_pad('1', $s['pad'], '0', STR_PAD_LEFT).'-'.$year;
    }

    public function assignNextNumber(Invoice $invoice, Company $company): void
    {
        if ($invoice->number !== null && $invoice->number !== '') {
            return;
        }

        $year = (int) now()->format('Y');
        $s = $this->referenceSettings($company);
        $prefix = $s['prefix'];
        $pad = $s['pad'];
        $newPattern = '/^'.preg_quote($prefix, '/').'-(\d+)-'.$year.'$/';
        $legacyPattern = '/^'.preg_quote($prefix, '/').'-'.$year.'-(\d+)$/';

        $max = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereNotNull('number')
            ->where('id', '!=', $invoice->id)
            ->pluck('number')
            ->reduce(function (int $carry, ?string $n) use ($newPattern, $legacyPattern): int {
                if (! is_string($n) || $n === '') {
                    return $carry;
                }
                if (preg_match($newPattern, $n, $m)) {
                    return max($carry, (int) $m[1]);
                }
                if (preg_match($legacyPattern, $n, $m)) {
                    return max($carry, (int) $m[1]);
                }

                return $carry;
            }, 0);

        $invoice->update([
            'number' => $prefix.'-'.str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT).'-'.$year,
        ]);
    }
}
