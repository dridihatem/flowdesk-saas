<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('AI report counsel') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 20px; }
        .content { white-space: pre-wrap; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>{{ __('AI report counsel') }}</h1>
    <p class="meta">
        {{ $companyName }} · {{ $from }} — {{ $to }} · {{ now()->format('Y-m-d H:i') }}
    </p>
    <div class="content">{{ $counsel }}</div>
    <p class="footer">{{ __('AI-generated content — review before sharing with clients.') }}</p>
</body>
</html>
