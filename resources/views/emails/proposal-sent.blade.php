<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @if (! empty($documentTemplates['quote_email_intro'] ?? null))
        <div>{!! $documentTemplates['quote_email_intro'] !!}</div>
    @endif
    <p>{{ __('Hello') }},</p>
    <p>{{ __(':company has sent you a quote for :amount :currency.', [
        'company' => $company->name,
        'amount' => flowdesk_format_minor((int) $proposal->amount, strtoupper($proposal->currency)),
        'currency' => $proposal->currency,
    ]) }}</p>
    @if ($proposal->valid_until)
        <p>{{ __('Valid until: :date', ['date' => $proposal->valid_until->format('Y-m-d')]) }}</p>
    @endif
    @if (! empty($documentTemplates['quote_email_footer'] ?? null))
        <div>{!! $documentTemplates['quote_email_footer'] !!}</div>
    @else
        <p>{{ __('Thank you.') }}</p>
    @endif
</body>
</html>
