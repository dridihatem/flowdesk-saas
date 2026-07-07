@php
    $company = $provider->company;
    $signUrl = flowdesk_tenant_url($company, route('providers.partnership.show', $provider, false));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello,') }}</p>
    <p>{{ __(':provider has signed the business provider partnership for :company. Open the workspace to review and sign on your side to finalize access.', ['provider' => $provider->name, 'company' => $company->name]) }}</p>
    <p>
        <a href="{{ $signUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">{{ __('Open partnership signing') }}</a>
    </p>
    <p style="font-size: 0.875rem; color: #64748b;">{{ __('If the button does not work, copy this link:') }}<br><span style="word-break: break-all;">{{ $signUrl }}</span></p>
</body>
</html>
