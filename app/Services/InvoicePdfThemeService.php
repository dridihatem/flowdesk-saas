<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PlatformSetting;

class InvoicePdfThemeService
{
    /**
     * Resolved PDF theme for an invoice (company choice + platform library + classic fallback).
     *
     * @return array{
     *     label: string,
     *     primary_color: string,
     *     accent_color: string,
     *     table_header_bg: string,
     *     table_header_text: string,
     *     border_color: string,
     *     text_color: string,
     *     muted_color: string,
     *     totals_grand_bg: string,
     *     totals_grand_text: string,
     *     pay_box_bg: string,
     *     panel_bg: string,
     *     compact_header: bool
     * }
     */
    public function forInvoice(Invoice $invoice, array $documentTemplates): array
    {
        return $this->forDocumentTemplates($documentTemplates);
    }

    /**
     * @return array<string, mixed>
     */
    public function forDocumentTemplates(array $documentTemplates): array
    {
        $row = PlatformSetting::query()->first();
        $library = is_array($row?->invoice_pdf_library) ? $row->invoice_pdf_library : [];

        $selected = (string) ($documentTemplates['invoice_pdf_template'] ?? 'classic');
        if ($selected === '' || $selected === 'classic') {
            return $this->classicDefaults();
        }

        if (! isset($library[$selected]) || ! is_array($library[$selected])) {
            return $this->classicDefaults();
        }

        return $this->mergePreset($this->classicDefaults(), $library[$selected]);
    }

    /**
     * @return array<string, mixed>
     */
    public function libraryPresets(): array
    {
        $row = PlatformSetting::query()->first();
        $library = is_array($row?->invoice_pdf_library) ? $row->invoice_pdf_library : [];

        return is_array($library) ? $library : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function classicDefaults(): array
    {
        return [
            'label' => 'Minimal',
            'primary_color' => '#18181b',
            'accent_color' => '#0d9488',
            'table_header_bg' => '#ffffff',
            'table_header_text' => '#71717a',
            'border_color' => '#d4d4d8',
            'text_color' => '#27272a',
            'muted_color' => '#71717a',
            'totals_grand_bg' => '#ffffff',
            'totals_grand_text' => '#0d9488',
            'pay_box_bg' => '#fafafa',
            'panel_bg' => '#fafafa',
            'compact_header' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $preset
     * @return array<string, mixed>
     */
    private function mergePreset(array $base, array $preset): array
    {
        $keys = [
            'label', 'primary_color', 'accent_color', 'table_header_bg', 'table_header_text',
            'border_color', 'text_color', 'muted_color', 'totals_grand_bg', 'totals_grand_text',
            'pay_box_bg', 'panel_bg',
        ];

        $merged = $base;

        foreach ($keys as $key) {
            if (! array_key_exists($key, $preset)) {
                continue;
            }
            $v = $preset[$key];
            if ($v === null || $v === '') {
                continue;
            }
            $merged[$key] = (string) $v;
        }

        if (array_key_exists('compact_header', $preset)) {
            $merged['compact_header'] = filter_var($preset['compact_header'], FILTER_VALIDATE_BOOL);
        }

        return $merged;
    }
}
