@php
    $pdfMeta = $pdfMeta ?? ['vat_percent' => 0.0, 'fiscal_stamp_enabled' => false];
    $pdfTheme = is_array($pdfTheme ?? null) ? $pdfTheme : [];
    $logoDataUri = $logoDataUri ?? null;
    $signatureDataUri = $signatureDataUri ?? null;
    $ic = flowdesk_invoice_currency($invoice);
    $showVatOnPdf = (int) $invoice->vat_amount > 0;
    $showFiscalStampOnPdf = (int) $invoice->fiscal_stamp_amount > 0;
    $vatPctPlain = rtrim(rtrim(sprintf('%.2f', (float) ($pdfMeta['vat_percent'] ?? 0)), '0'), '.');
    $completedTotal = $invoice->completedPaymentsTotalMinor();
    $balanceMinor = $balanceMinor ?? max(0, $invoice->amount - $completedTotal);
    $paymentUrl = $paymentUrl ?? null;
    $paymentQrDataUri = $paymentQrDataUri ?? null;
    $company = $invoice->company;
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
    $payBg = $pdfTheme['pay_box_bg'] ?? '#fafafa';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: {{ $compact ? '8.5pt' : '9pt' }};
            color: {{ $text }};
            line-height: 1.5;
            margin: 0;
            padding: {{ $pad }};
            background: #fff;
        }
        .doc { width: 100%; }

        /* ── Top banner ── */
        table.doc-top { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '6mm' : '8mm' }}; }
        table.doc-top td { vertical-align: middle; padding: 0; }
        .doc-logo {
            max-height: {{ $compact ? '14mm' : '18mm' }};
            max-width: {{ $compact ? '48mm' : '56mm' }};
            object-fit: contain;
            display: block;
        }
        .doc-invoice-label {
            font-size: {{ $compact ? '26pt' : '32pt' }};
            font-weight: normal;
            color: {{ $accent }};
            text-align: right;
            letter-spacing: 0.08em;
            line-height: 1;
            margin: 0;
        }
        .doc-rule {
            height: 0;
            border: none;
            border-top: 1.5px solid {{ $accent }};
            margin: 0 0 {{ $compact ? '5mm' : '6mm' }} 0;
        }

        /* ── Parties ── */
        table.doc-parties { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '5mm' : '6mm' }}; }
        table.doc-parties td { vertical-align: top; width: 50%; padding: 0; }
        table.doc-parties td:first-child { padding-right: 6mm; }
        table.doc-parties td:last-child { padding-left: 6mm; border-left: 0.5px solid {{ $border }}; }
        .doc-label {
            font-size: {{ $compact ? '6.5pt' : '7pt' }};
            color: {{ $accent }};
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: bold;
            margin: 0 0 2mm 0;
        }
        .doc-name {
            font-size: {{ $compact ? '10pt' : '11pt' }};
            font-weight: bold;
            color: {{ $primary }};
            margin: 0 0 1.5mm 0;
        }
        .doc-line { font-size: {{ $compact ? '7.5pt' : '8pt' }}; color: {{ $text }}; margin-top: 0.5mm; }

        /* ── Meta chips row ── */
        table.doc-meta { width: 100%; border-collapse: collapse; margin-bottom: {{ $compact ? '5mm' : '6mm' }}; }
        table.doc-meta td {
            padding: {{ $compact ? '2mm 3mm' : '2.5mm 3.5mm' }};
            border: 0.5px solid {{ $border }};
            text-align: center;
            font-size: {{ $compact ? '7pt' : '7.5pt' }};
            width: 20%;
        }
        .doc-meta-lbl { display: block; color: {{ $muted }}; font-size: {{ $compact ? '6pt' : '6.5pt' }}; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.8mm; }
        .doc-meta-val { display: block; font-weight: bold; color: {{ $primary }}; }

        /* ── Line items ── */
        .doc-legend { margin: 0 0 2mm 0; font-size: {{ $compact ? '6.5pt' : '7pt' }}; color: {{ $muted }}; }
        table.doc-lines { width: 100%; border-collapse: collapse; font-size: {{ $compact ? '8pt' : '8.5pt' }}; }
        table.doc-lines thead th {
            padding: {{ $compact ? '2mm 2mm' : '2.5mm 2.5mm' }};
            background: {{ $thBg }};
            color: {{ $thText }};
            font-weight: bold;
            font-size: {{ $compact ? '6.5pt' : '7pt' }};
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid {{ $accent }};
            text-align: center;
        }
        table.doc-lines tbody td {
            padding: {{ $compact ? '2.2mm 2mm' : '2.8mm 2.5mm' }};
            border-bottom: 0.5px solid {{ $border }};
            vertical-align: top;
        }
        table.doc-lines th.desc, table.doc-lines td.desc { text-align: left; }
        table.doc-lines th.num, table.doc-lines td.num { text-align: right; white-space: nowrap; }
        table.doc-lines th.pct, table.doc-lines td.pct { text-align: center; width: 10%; }

        /* ── Bottom: totals + settlement side by side ── */
        table.doc-bottom { width: 100%; border-collapse: collapse; margin-top: {{ $compact ? '4mm' : '5mm' }}; }
        table.doc-bottom td { vertical-align: top; padding: 0; }
        table.doc-bottom td.pay-col { width: 46%; padding-right: 5mm; }
        table.doc-bottom td.totals-col { width: 54%; }

        table.doc-totals { width: 100%; border-collapse: collapse; font-size: {{ $compact ? '8.5pt' : '9pt' }}; }
        table.doc-totals td {
            padding: {{ $compact ? '1.5mm 0' : '2mm 0' }};
            border-bottom: 0.5px solid {{ $border }};
        }
        table.doc-totals td.lbl { color: {{ $muted }}; width: 55%; }
        table.doc-totals td.val { text-align: right; font-weight: bold; white-space: nowrap; color: {{ $text }}; }
        table.doc-totals tr.grand td {
            border-bottom: none;
            border-top: 1.5px solid {{ $accent }};
            padding-top: {{ $compact ? '2.5mm' : '3mm' }};
            font-size: {{ $compact ? '11pt' : '12pt' }};
            font-weight: bold;
            background: {{ $grandBg }};
            color: {{ $grandText }};
        }
        table.doc-totals tr.grand td.lbl { color: {{ $primary }}; font-size: {{ $compact ? '9pt' : '10pt' }}; }

        .doc-pay {
            background: {{ $payBg }};
            border: 0.5px solid {{ $border }};
            padding: {{ $compact ? '3mm' : '3.5mm' }};
            font-size: {{ $compact ? '8pt' : '8.5pt' }};
        }
        .doc-pay-title {
            font-size: {{ $compact ? '6.5pt' : '7pt' }};
            color: {{ $accent }};
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: bold;
            margin-bottom: 2mm;
        }
        table.doc-pay-inner { width: 100%; border-collapse: collapse; }
        table.doc-pay-inner td { padding: 1mm 0; border: none; }
        table.doc-pay-inner td.r { text-align: right; font-weight: bold; }
        table.doc-pay-inner tr.due td {
            padding-top: 2mm;
            border-top: 0.5px solid {{ $border }};
            font-size: {{ $compact ? '9.5pt' : '10.5pt' }};
            color: {{ $accent }};
        }
        .doc-pay-qr { margin-top: 3mm; text-align: center; }
        .doc-pay-qr img { width: 28mm; height: 28mm; }
        .doc-pay-qr-cap { font-size: 7pt; color: {{ $muted }}; margin-top: 1mm; }

        .doc-signature { margin-top: {{ $compact ? '6mm' : '8mm' }}; text-align: right; }
        .doc-signature-img {
            max-height: {{ $compact ? '16mm' : '22mm' }};
            max-width: 55mm;
            object-fit: contain;
            display: inline-block;
        }
        .doc-signature-cap { margin-top: 1mm; font-size: 7pt; color: {{ $muted }}; }

        .doc-notes {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 0.5px solid {{ $border }};
            font-size: {{ $compact ? '8pt' : '8.5pt' }};
        }
        .doc-notes strong { color: {{ $muted }}; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em; }

        .doc-footer {
            margin-top: 6mm;
            padding-top: 3mm;
            border-top: 0.5px solid {{ $border }};
            font-size: 7pt;
            color: {{ $muted }};
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="doc">

    {{-- Logo + Invoice title --}}
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
                <div class="doc-invoice-label">{{ __('Invoice') }}</div>
            </td>
        </tr>
    </table>
    <hr class="doc-rule" />

    {{-- From / Bill to --}}
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
                @if ($invoice->client)
                    <div class="doc-name">{{ $invoice->client->name }}</div>
                    @if ($invoice->client->code)
                        <div class="doc-line">{{ __('Client code') }}: {{ $invoice->client->code }}</div>
                    @endif
                    @if ($invoice->client->email)
                        <div class="doc-line">{{ $invoice->client->email }}</div>
                    @endif
                @else
                    <div class="doc-name">—</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Reference / dates row --}}
    <table class="doc-meta">
        <tr>
            <td>
                <span class="doc-meta-lbl">{{ __('Reference') }}</span>
                <span class="doc-meta-val">{{ $invoice->number ?? $invoice->id }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Date') }}</span>
                <span class="doc-meta-val">{{ $invoice->created_at?->format('d/m/Y') ?? '—' }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Due date') }}</span>
                <span class="doc-meta-val">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Status') }}</span>
                <span class="doc-meta-val">{{ $invoice->status->label() }}</span>
            </td>
            <td>
                <span class="doc-meta-lbl">{{ __('Currency') }}</span>
                <span class="doc-meta-val">{{ $ic }}</span>
            </td>
        </tr>
    </table>

    <p class="doc-legend">{{ __('PDF amounts legend', ['currency' => $ic]) }}</p>

    {{-- Line items --}}
    <table class="doc-lines">
        <thead>
            <tr>
                <th class="desc text-start" style="width: {{ $showVatOnPdf ? '36%' : '48%' }};">{{ __('invoice_pdf_designation') }}</th>
                @if ($showVatOnPdf)
                    <th class="pct text-start">{{ __('VAT %') }}</th>
                @endif
                <th class="num text-start" style="width: 8%;">{{ __('Qty') }}</th>
                <th class="num text-start" style="width: {{ $showVatOnPdf ? '14%' : '22%' }};">{{ __('Unit price (HT)') }}</th>
                <th class="num text-start" style="width: {{ $showVatOnPdf ? '14%' : '22%' }};">{{ __('Line total (HT)') }}</th>
                @if ($showVatOnPdf)
                    <th class="num text-start" style="width: 14%;">{{ __('Line total (TTC)') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $row)
                @php
                    $lineHt = $row->total_amount;
                    $lineTtc = $invoice->lineTotalTtcDisplayMinor($lineHt);
                @endphp
                <tr>
                    <td class="desc text-start">{{ $row->description }}</td>
                    @if ($showVatOnPdf)
                        <td class="pct text-start">{{ $vatPctPlain }}</td>
                    @endif
                    <td class="num text-start">{{ $row->quantity }}</td>
                    <td class="num text-start">{{ flowdesk_format_minor((int) $row->unit_amount, $ic) }}</td>
                    <td class="num text-start">{{ flowdesk_format_minor((int) $lineHt, $ic) }}</td>
                    @if ($showVatOnPdf)
                        <td class="num text-start">{{ flowdesk_format_minor((int) $lineTtc, $ic) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Settlement + Totals --}}
    <table class="doc-bottom">
        <tr>
            <td class="pay-col text-start">
                <div class="doc-pay">
                    <div class="doc-pay-title">{{ __('Settlement') }}</div>
                    <table class="doc-pay-inner">
                        <tr>
                            <td>{{ __('Advance paid') }}</td>
                            <td class="r text-start">{{ flowdesk_format_minor((int) $completedTotal, $ic) }} {{ $ic }}</td>
                        </tr>
                        <tr class="due">
                            <td><strong>{{ __('Balance due') }}</strong></td>
                            <td class="r text-start"><strong>{{ flowdesk_format_minor((int) $balanceMinor, $ic) }} {{ $ic }}</strong></td>
                        </tr>
                    </table>
                    @if ($paymentQrDataUri && $balanceMinor > 0)
                        <div class="doc-pay-qr">
                            <img src="{{ $paymentQrDataUri }}" alt="{{ __('invoice_pdf_scan_to_pay') }}" />
                            <div class="doc-pay-qr-cap">{{ __('invoice_pdf_scan_to_pay') }}</div>
                            @if ($paymentUrl)
                                <div class="doc-pay-qr-cap" style="font-size:6pt; word-break:break-all;">{{ $paymentUrl }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </td>
            <td class="totals-col text-start">
                <table class="doc-totals">
                    <tr>
                        <td class="lbl text-start">{{ __('Total HT') }}</td>
                        <td class="val text-start">{{ flowdesk_format_minor((int) $invoice->subtotal_amount, $ic) }} {{ $ic }}</td>
                    </tr>
                    @if ($showVatOnPdf)
                        <tr>
                            <td class="lbl text-start">{{ __('invoice_pdf_vat_line', ['rate' => $vatPctPlain]) }}</td>
                            <td class="val text-start">{{ flowdesk_format_minor((int) $invoice->vat_amount, $ic) }} {{ $ic }}</td>
                        </tr>
                    @endif
                    @if ($showFiscalStampOnPdf)
                        <tr>
                            <td class="lbl text-start">{{ __('invoice_pdf_fiscal_stamp') }}</td>
                            <td class="val text-start">{{ flowdesk_format_minor((int) $invoice->fiscal_stamp_amount, $ic) }} {{ $ic }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td class="lbl text-start">{{ __('Total TTC') }}</td>
                        <td class="val text-start">{{ flowdesk_format_minor((int) $invoice->amount, $ic) }} {{ $ic }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (! empty($signatureDataUri))
        <div class="doc-signature">
            <img src="{{ $signatureDataUri }}" alt="" class="doc-signature-img" />
            <div class="doc-signature-cap">{{ __('invoice_pdf_company_signature') }}</div>
        </div>
    @endif

    @if (! empty($invoice->customer_notes))
        <div class="doc-notes">
            <strong>{{ __('Customer notes') }}</strong>
            <p style="margin: 1.5mm 0 0; white-space: pre-wrap;">{{ $invoice->customer_notes }}</p>
        </div>
    @endif

    @if (! empty($documentTemplates['invoice_pdf_footer'] ?? null))
        <div class="doc-footer">{!! $documentTemplates['invoice_pdf_footer'] !!}</div>
    @endif
</div>
</body>
</html>
