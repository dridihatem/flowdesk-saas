@php
    $company = $provider->company;
    $dashboardUrl = flowdesk_tenant_url($company, route('provider.dashboard', [], false));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello :name,', ['name' => $provider->name]) }}</p>
    <p>{{ __(':company has signed the partnership. Your provider account is now fully active.', ['company' => $company->name]) }}</p>
    <p>
        <a href="{{ $dashboardUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">{{ __('Open provider dashboard') }}</a>
    </p>
</body>
</html>
