<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewaySettingsController extends Controller
{
    public function edit(): View
    {
        $row = PlatformSetting::query()->first() ?? new PlatformSetting;
        $payment = is_array($row->payment_credentials) ? $row->payment_credentials : [];

        return view('admin.payment-gateways', compact('payment'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stripe_public_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['nullable', 'string', 'in:sandbox,live'],
            'flouci_public_key' => ['nullable', 'string', 'max:255'],
            'flouci_secret_key' => ['nullable', 'string', 'max:255'],
            'flouci_api_base' => ['nullable', 'string', 'max:500'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_rib' => ['nullable', 'string', 'max:64'],
            'bank_bic' => ['nullable', 'string', 'max:32'],
            'bank_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = PlatformSetting::query()->first() ?? new PlatformSetting;
        $existing = is_array($row->payment_credentials) ? $row->payment_credentials : [];

        $payment = array_merge($existing, array_filter([
            'stripe_public_key' => $data['stripe_public_key'] ?? null,
            'stripe_secret_key' => $data['stripe_secret_key'] ?? null,
            'stripe_webhook_secret' => $data['stripe_webhook_secret'] ?? null,
            'paypal_client_id' => $data['paypal_client_id'] ?? null,
            'paypal_secret' => $data['paypal_secret'] ?? null,
            'paypal_mode' => $data['paypal_mode'] ?? null,
            'flouci_public_key' => $data['flouci_public_key'] ?? null,
            'flouci_secret_key' => $data['flouci_secret_key'] ?? null,
            'flouci_api_base' => $data['flouci_api_base'] ?? null,
            'bank_account_holder' => $data['bank_account_holder'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_rib' => $data['bank_rib'] ?? null,
            'bank_bic' => $data['bank_bic'] ?? null,
            'bank_instructions' => $data['bank_instructions'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        if (empty($data['stripe_secret_key'] ?? null) && isset($existing['stripe_secret_key'])) {
            $payment['stripe_secret_key'] = $existing['stripe_secret_key'];
        }
        if (empty($data['paypal_secret'] ?? null) && isset($existing['paypal_secret'])) {
            $payment['paypal_secret'] = $existing['paypal_secret'];
        }
        if (empty($data['flouci_secret_key'] ?? null) && isset($existing['flouci_secret_key'])) {
            $payment['flouci_secret_key'] = $existing['flouci_secret_key'];
        }

        if (! $row->exists) {
            $row->theme_defaults = null;
        }
        $row->payment_credentials = $payment;
        $row->save();

        return redirect()->route('admin.payment-gateways.edit')->with('status', __('Platform payment gateways saved. All workspaces use these keys for customer invoice payments.'));
    }
}
