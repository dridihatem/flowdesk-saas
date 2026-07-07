<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @if (! empty($isReminder))
        <p><strong>{{ __('This is a reminder.') }}</strong></p>
    @endif
    @if (! empty($documentTemplates['invoice_email_intro'] ?? null))
        <div>{!! $documentTemplates['invoice_email_intro'] !!}</div>
    @endif
    <p>{{ __('Hello') }},</p>
    <p>{{ __(':company has sent you an invoice for :amount :currency.', [
        'company' => $company->name,
        'amount' => flowdesk_format_minor((int) $invoice->amount, flowdesk_invoice_currency($invoice)),
        'currency' => $invoice->currency,
    ]) }}</p>
    @if ($invoice->due_date)
        <p>{{ __('Due date: :date', ['date' => $invoice->due_date->format('Y-m-d')]) }}</p>
    @endif
    @if (! empty($documentTemplates['invoice_email_footer'] ?? null))
        <div>{!! $documentTemplates['invoice_email_footer'] !!}</div>
    @else
        <p>{{ __('Thank you.') }}</p>
    @endif
</body>
</html>
