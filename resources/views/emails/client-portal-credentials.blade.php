@php
    $loginUrl = flowdesk_tenant_url($company, route('login', [], false));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello :name,', ['name' => $client->name]) }}</p>
    <p>{{ __('client_credentials_mail_intro', ['company' => $company->name]) }}</p>
    <div style="margin: 1.25rem 0; padding: 1rem; background: #f8fafc; border-radius: 8px;">
        <p style="margin: 0 0 0.5rem;"><strong>{{ __('Email') }}</strong> : {{ $client->email }}</p>
        <p style="margin: 0;"><strong>{{ __('Password') }}</strong> : <code style="background: #e2e8f0; padding: 0.15rem 0.4rem; border-radius: 4px;">{{ $plainPassword }}</code></p>
    </div>
    <p>
        <a href="{{ $loginUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">{{ __('client_credentials_mail_button') }}</a>
    </p>
    <p style="font-size: 0.875rem; color: #64748b;">{{ __('client_credentials_mail_security_note') }}</p>
    <p style="font-size: 0.875rem; color: #64748b;">{{ __('If the button does not work, copy this link:') }}<br><span style="word-break: break-all;">{{ $loginUrl }}</span></p>
</body>
</html>
