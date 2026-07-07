@php
    $pdfMeta = $pdfMeta ?? ['vat_percent' => 0.0, 'fiscal_stamp_enabled' => false];
    $pdfTheme = is_array($pdfTheme ?? null) ? $pdfTheme : [];
    $logoDataUri = $logoDataUri ?? null;
    $signatureDataUri = $signatureDataUri ?? null;
    $ic = strtoupper($proposal->currency);
    $showVatOnPdf = (int) $proposal->vat_amount > 0;
    $showFiscalStampOnPdf = (int) $proposal->fiscal_stamp_amount > 0;
    $vatPctPlain = rtrim(rtrim(sprintf('%.2f', (float) ($pdfMeta['vat_percent'] ?? 0)), '0'), '.');
    $company = $proposal->company;
    $addrLine = trim(implode(' ', array_filter([$company?->postal_code, $company?->city])));
    $sellerLines = collect([
        $company?->address_line1,
        $addrLine !== '' ? $addrLine : null,
        $company?->phone,
        $company?->contact_email,
        $company?->tax_id ? __('VAT / TVA').': '.$company->tax_id : null,
    ])->filter()->values();
    $compact = filter_var($pdfTheme['compact_header'] ?? true, FILTER_VALIDATE_BOOL);
    $pad = $compact ? '14mm 16mm' : '16mm 18mm';
    $thBg = $pdfTheme['table_header_bg'] ?? '#ffffff';
    $thText = $pdfTheme['table_header_text'] ?? ($pdfTheme['muted_color'] ?? '#71717a');
    $border = $pdfTheme['border_color'] ?? '#d4d4d8';
    $text = $pdfTheme['text_color'] ?? '#27272a';
    $muted = $pdfTheme['muted_color'] ?? '#71717a';
    $primary = $pdfTheme['primary_color'] ?? '#18181b';
    $accent = $pdfTheme['accent_color'] ?? '#0d9488';
    $grandBg = $pdfTheme['totals_grand_bg'] ?? '#ffffff';
    $grandText = $pdfTheme['totals_grand_text'] ?? $accent;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: {{ $compact ? '8.5pt' : '9pt' }}; color: {{ $text }}; line-height: 1.5; margin: 0; padding: {{ $pad }}; background: #fff; }
        .doc { width: 100%; }
        table.doc-top { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '6mm' : '8mm' }}; }
        table.doc-top td { vertical-align: middle; padding: 0; }
        .doc-logo { max-height: {{ $compact ? '14mm' : '18mm' }}; max-width: {{ $compact ? '48mm' : '56mm' }}; object-fit: contain; display: block; }
        .doc-title { font-size: {{ $compact ? '26pt' : '32pt' }}; font-weight: normal; color: {{ $accent }}; text-align: right; letter-spacing: 0.08em; line-height: 1; margin: 0; }
        .doc-rule { height: 0; border: none; border-top: 1.5px solid {{ $accent }}; margin: 0 0 {{ $compact ? '5mm' : '6mm' }} 0; }
        table.doc-parties { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '5mm' : '6mm' }}; }
        table.doc-parties td { vertical-align: top; width: 50%; padding: 0; }
        table.doc-parties td:first-child { padding-right: 6mm; }
        table.doc-parties td:last-child { padding-left: 6mm; border-left: 0.5px solid {{ $border }}; }
        .doc-label { font-size: {{ $compact ? '6.5pt' : '7pt' }}; color: {{ $accent }}; text-transform: uppercase; letter-spacing: 0.12em; font-weight: bold; margin: 0 0 2mm 0; }
        .doc-name { font-size: {{ $compact ? '10pt' : '11pt' }}; font-weight: bold; color: {{ $primary }}; margin: 0 0 1.5mm 0; }
        .doc-line { font-size: {{ $compact ? '7.5pt' : '8pt' }}; color: {{ $text }}; margin-top: 0.5mm; }
        table.doc-meta { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '5mm' : '6mm' }}; }
        table.doc-meta td { padding: {{ $compact ? '2mm 3mm' : '2.5mm 3.5mm' }}; border: 0.5px solid {{ $border }}; text-align: center; font-size: {{ $compact ? '7pt' : '7.5pt' }}; width: 25%; }
        .doc-meta-lbl { display: block; color: {{ $muted }}; font-size: {{ $compact ? '6pt' : '6.5pt' }}; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.8mm; }
        .doc-meta-val { display: block; font-weight: bold; color: {{ $primary }}; }
        table.doc-lines { width: 100%; border-collapse: collapse; font-size: {{ $compact ? '8pt' : '8.5pt' }}; }
        table.doc-lines thead th { padding: {{ $compact ? '2mm 2mm' : '2.5mm 2.5mm' }}; background: {{ $thBg }}; color: {{ $thText }}; font-weight: bold; font-size: {{ $compact ? '6.5pt' : '7pt' }}; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1.5px solid {{ $accent }}; text-align: center; }
        table.doc-lines tbody td { padding: {{ $compact ? '2.2mm 2mm' : '2.8mm 2.5mm' }}; border-bottom: 0.5px solid {{ $border }}; vertical-align: top; }
        table.doc-lines th.desc, table.doc-lines td.desc { text-align: left; }
        table.doc-lines th.num, table.doc-lines td.num { text-align: right; white-space: nowrap; }
        table.doc-totals { width: 100%; border-collapse: collapse; font-size: {{ $compact ? '8.5pt' : '9pt' }}; margin-top: {{ $compact ? '4mm' : '5mm' }}; }
        table.doc-totals td { padding: {{ $compact ? '1.5mm 0' : '2mm 0' }}; border-bottom: 0.5px solid {{ $border }}; }
        table.doc-totals td.lbl { color: {{ $muted }}; width: 55%; }
        table.doc-totals td.val { text-align: right; font-weight: bold; white-space: nowrap; color: {{ $text }}; }
        table.doc-totals tr.grand td { border-bottom: none; border-top: 1.5px solid {{ $accent }}; padding-top: {{ $compact ? '2.5mm' : '3mm' }}; font-size: {{ $compact ? '11pt' : '12pt' }}; font-weight: bold; background: {{ $grandBg }}; color: {{ $grandText }}; }
        .doc-signature { margin-top: {{ $compact ? '6mm' : '8mm' }}; text-align: right; }
        .doc-signature-img { max-height: {{ $compact ? '16mm' : '22mm' }}; max-width: 55mm; object-fit: contain; display: inline-block; }
        .doc-notes { margin-top: 5mm; padding-top: 3mm; border-top: 0.5px solid {{ $border }}; font-size: {{ $compact ? '8pt' : '8.5pt' }}; }
        .doc-footer { margin-top: 6mm; padding-top: 3mm; border-top: 0.5px solid {{ $border }}; font-size: 7pt; color: {{ $muted }}; text-align: center; }
    </style>
</head>
<body>
<div class="doc">
    <table class="doc-top">
        <tr>
            <td class="text-start"style="width: 55%;">
                @if (! empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" alt="" class="doc-logo" />
                @else
                    <div class="doc-name" style="font-size: {{ $compact ? '13pt' : '15pt' }};">{{ $company?->name ?? '—' }}</div>
                @endif
            </td>
            <td class="text-start"style="width: 45%;">
                <div class="doc-title">{{ __('Quote') }}</div>
            </td>
        </tr>
    </table>
    <hr class="doc-rule" />

    <table class="doc-parties">
        <tr>
            <td>
                <div class="doc-label">{{ __('Seller') }}</div>
                <div class="doc-name">{{ $company?->name ?? '—' }}</div>
                @foreach ($sellerLines as $line)
                    <div class="doc-line">{{ $line }}</div>
                @endforeach
            </td>
            <td>
                <div class="doc-label">{{ __('Bill to') }}</div>
                @if ($proposal->client)
                    <div class="doc-name">{{ $proposal->client->name }}</div>
                    @if ($proposal->client->email)
                        <div class="doc-line">{{ $proposal->client->email }}</div>
                    @endif
                @else
                    <div class="doc-name">—</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="doc-meta">
        <tr>
            <td>
                <span class="doc-meta-lbl">{{ __('Reference') }}</span>
                <span class="doc-meta-val">{{ $proposal->number ?? $proposal->id }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Date') }}</span>
                <span class="doc-meta-val">{{ $proposal->created_at?->format('d/m/Y') ?? '—' }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Valid until') }}</span>
                <span class="doc-meta-val">{{ $proposal->valid_until?->format('d/m/Y') ?? '—' }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Currency') }}</span>
                <span class="doc-meta-val">{{ $ic }}</span>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 2mm; font-size: 7pt; color: {{ $muted }};">{{ $proposal->name }}</p>

    <table class="doc-lines">
        <thead>
            <tr>
                <th class="desc text-start">{{ __('invoice_pdf_designation') }}</th>
                <th class="num text-start">{{ __('Qty') }}</th>
                <th class="num text-start">{{ __('Unit price (HT)') }}</th>
                <th class="num text-start">{{ __('Line total (HT)') }}</th>
                @if ($showVatOnPdf)
                    <th class="num text-start">{{ __('Line total (TTC)') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($proposal->items as $row)
                @php
                    $lineHt = $row->total_amount;
                    $lineTtc = $proposal->lineTotalTtcDisplayMinor($lineHt);
                @endphp
                <tr>
                    <td class="desc text-start">{{ $row->description }}</td>
                    <td class="num text-start">{{ $row->quantity }}</td>
                    <td class="num text-start">{{ flowdesk_format_minor((int) $row->unit_amount, $ic) }}</td>
                    <td class="num text-start">{{ flowdesk_format_minor((int) $lineHt, $ic) }}</td>
                    @if ($showVatOnPdf)
                        <td class="num text-start">{{ flowdesk_format_minor((int) $lineTtc, $ic) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td class="desc text-start" colspan="{{ $showVatOnPdf ? 5 : 4 }}">{{ $proposal->name }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="doc-totals">
        <tr>
            <td class="lbl text-start">{{ __('Total HT') }}</td>
            <td class="val text-start">{{ flowdesk_format_minor((int) $proposal->subtotal_amount, $ic) }} {{ $ic }}</td>
        </tr>
        @if ($showVatOnPdf)
            <tr>
                <td class="lbl text-start">{{ __('invoice_pdf_vat_line', ['rate' => $vatPctPlain]) }}</td>
                <td class="val text-start">{{ flowdesk_format_minor((int) $proposal->vat_amount, $ic) }} {{ $ic }}</td>
            </tr>
        @endif
        @if ($showFiscalStampOnPdf)
            <tr>
                <td class="lbl text-start">{{ __('invoice_pdf_fiscal_stamp') }}</td>
                <td class="val text-start">{{ flowdesk_format_minor((int) $proposal->fiscal_stamp_amount, $ic) }} {{ $ic }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="lbl text-start">{{ __('Total TTC') }}</td>
            <td class="val text-start">{{ flowdesk_format_minor((int) $proposal->amount, $ic) }} {{ $ic }}</td>
        </tr>
    </table>

    @if (! empty($signatureDataUri))
        <div class="doc-signature">
            <img src="{{ $signatureDataUri }}" alt="" class="doc-signature-img" />
        </div>
    @endif

    @if (! empty($proposal->customer_notes))
        <div class="doc-notes">
            <strong>{{ __('Customer notes') }}</strong>
            <p style="margin: 1.5mm 0 0; white-space: pre-wrap;">{{ $proposal->customer_notes }}</p>
        </div>
    @endif

    @if (! empty($documentTemplates['invoice_pdf_footer'] ?? null))
        <div class="doc-footer">{!! $documentTemplates['invoice_pdf_footer'] !!}</div>
    @endif
</div>
</body>
</html>
