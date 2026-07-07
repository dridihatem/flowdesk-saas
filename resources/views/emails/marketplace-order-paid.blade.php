<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello :name,', ['name' => $order->customer_name]) }}</p>
    <p>{{ __('marketplace_order_paid_mail_intro', ['number' => $order->order_number]) }}</p>
    <p class="mt-2 text-sm"><strong>{{ __('marketing_checkout_payment_reference_label') }}:</strong> <code>{{ $order->paymentReferenceLabel() }}</code></p>

    @if ($installedModuleNames !== [])
        <div style="margin: 1.25rem 0; padding: 1rem; background: #ecfdf5; border-radius: 8px; border: 1px solid #a7f3d0;">
            <p style="margin: 0 0 0.5rem; font-weight: 600;">{{ __('marketplace_order_paid_mail_workspace_installed') }}</p>
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach ($installedModuleNames as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
            @if ($company && Route::has('settings.modules'))
                <p style="margin: 0.75rem 0 0;">
                    <a href="{{ flowdesk_tenant_url($company, route('settings.modules', [], false)) }}" style="color: #4f46e5; font-weight: 600;">{{ __('marketplace_order_paid_mail_open_modules') }}</a>
                </p>
            @endif
        </div>
    @endif

    <div style="margin: 1.25rem 0; padding: 1rem; background: #f8fafc; border-radius: 8px;">
        <p style="margin: 0 0 0.75rem; font-weight: 600;">{{ __('marketplace_order_paid_mail_downloads_heading') }}</p>
        <ul style="margin: 0; padding-left: 0; list-style: none;">
            @foreach ($downloads as $download)
                <li style="margin-bottom: 0.75rem;">
                    <strong>{{ $download['name'] }}</strong>
                    @if (! empty($download['url']))
                        <br>
                        <a href="{{ $download['url'] }}" style="color: #4f46e5;">{{ __('marketplace_order_paid_mail_download_link') }}</a>
                    @elseif (($download['attach_path'] ?? null) !== null)
                        <br><span style="font-size: 0.875rem; color: #64748b;">{{ __('marketplace_order_paid_mail_attached_zip') }}</span>
                    @else
                        <br><span style="font-size: 0.875rem; color: #64748b;">{{ __('marketing_checkout_no_zip') }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p style="margin: 0.75rem 0 0; font-size: 0.875rem; color: #64748b;">{{ __('marketplace_order_paid_mail_links_expire') }}</p>
    </div>

    <p style="font-size: 0.875rem; color: #64748b;">{{ __('marketplace_order_paid_mail_footer') }}</p>
</body>
</html>
