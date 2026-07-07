<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Services\CompanyThemeService;
use App\Services\InvoicePaymentGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewaySettingsController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, CompanyThemeService $themes, InvoicePaymentGatewayService $gateways): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $settings = $themes->ensureSettings($company);
        $payment = is_array($settings->payment_credentials) ? $settings->payment_credentials : [];
        $enabled = is_array($payment['enabled_gateways'] ?? null) ? $payment['enabled_gateways'] : $gateways->enabledGatewayIds($company);
        $platform = $gateways->platformCredentials();

        return view('settings.payment-gateways', [
            'company' => $company,
            'payment' => $payment,
            'enabled' => $enabled,
            'platform' => $platform,
            'resolved' => $gateways->resolvedCredentials($company),
        ]);
    }

    public function update(Request $request, CompanyThemeService $themes): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $data = $request->validate([
            'enabled_gateways' => ['nullable', 'array'],
            'enabled_gateways.*' => ['string', 'in:stripe,paypal,flouci,bank_transfer'],
            'stripe_public_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['nullable', 'string', 'in:sandbox,live'],
            'flouci_public_key' => ['nullable', 'string', 'max:255'],
            'flouci_secret_key' => ['nullable', 'string', 'max:255'],
            'flouci_api_base' => ['nullable', 'string', 'max:500'],
            'bank_instructions' => ['nullable', 'string', 'max:4000'],
        ]);

        $settings = $themes->ensureSettings($company);
        $existing = is_array($settings->payment_credentials) ? $settings->payment_credentials : [];

        $payment = $existing;
        $payment['enabled_gateways'] = array_values($data['enabled_gateways'] ?? []);

        foreach (['stripe_public_key', 'paypal_client_id', 'paypal_mode', 'flouci_public_key', 'flouci_api_base', 'bank_instructions'] as $key) {
            if (array_key_exists($key, $data)) {
                $payment[$key] = $data[$key] !== '' ? $data[$key] : null;
            }
        }

        foreach (['stripe_secret_key', 'paypal_secret', 'flouci_secret_key'] as $secretKey) {
            if (! empty($data[$secretKey])) {
                $payment[$secretKey] = $data[$secretKey];
            } elseif (isset($existing[$secretKey])) {
                $payment[$secretKey] = $existing[$secretKey];
            }
        }

        $settings->payment_credentials = $payment;
        $settings->save();

        return redirect()
            ->route('settings.payment-gateways')
            ->with('status', __('Company payment methods saved.'));
    }
}
