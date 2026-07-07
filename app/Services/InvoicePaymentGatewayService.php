<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\PlatformSetting;

class InvoicePaymentGatewayService
{
    public const GATEWAY_STRIPE = 'stripe';

    public const GATEWAY_PAYPAL = 'paypal';

    public const GATEWAY_FLOUCI = 'flouci';

    public const GATEWAY_BANK = 'bank_transfer';

    /**
     * @return list<string>
     */
    public function allGatewayIds(): array
    {
        return [
            self::GATEWAY_STRIPE,
            self::GATEWAY_PAYPAL,
            self::GATEWAY_FLOUCI,
            self::GATEWAY_BANK,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function platformCredentials(): array
    {
        $row = PlatformSetting::query()->first();
        $p = $row?->payment_credentials;

        return is_array($p) ? $p : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function companyPaymentSettings(Company $company): array
    {
        $settings = CompanySetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();
        $p = $settings?->payment_credentials;

        return is_array($p) ? $p : [];
    }

    /**
     * Merged API keys: company overrides platform when set.
     *
     * @return array<string, mixed>
     */
    public function resolvedCredentials(Company $company): array
    {
        $platform = $this->platformCredentials();
        $companyCreds = $this->companyPaymentSettings($company);

        $merged = array_merge($platform, array_filter(
            $companyCreds,
            fn ($v, $k) => ! in_array($k, ['enabled_gateways'], true) && $v !== null && $v !== '',
            ARRAY_FILTER_USE_BOTH
        ));

        if (! empty($companyCreds['bank_instructions'])) {
            $merged['bank_instructions'] = $companyCreds['bank_instructions'];
        } elseif (! empty($platform['bank_instructions'])) {
            $merged['bank_instructions'] = $platform['bank_instructions'];
        }

        return $merged;
    }

    /**
     * @return list<string>
     */
    public function enabledGatewayIds(Company $company): array
    {
        $companyCreds = $this->companyPaymentSettings($company);
        $enabled = $companyCreds['enabled_gateways'] ?? null;

        if (is_array($enabled) && $enabled !== []) {
            return array_values(array_intersect($this->allGatewayIds(), $enabled));
        }

        return $this->autoEnabledGatewayIds($company);
    }

    /**
     * @return list<string>
     */
    private function autoEnabledGatewayIds(Company $company): array
    {
        $creds = $this->resolvedCredentials($company);
        $out = [];

        if ($this->stripeReady($creds)) {
            $out[] = self::GATEWAY_STRIPE;
        }
        if ($this->paypalReady($creds)) {
            $out[] = self::GATEWAY_PAYPAL;
        }
        if ($this->flouciReady($creds)) {
            $out[] = self::GATEWAY_FLOUCI;
        }
        if ($this->bankTransferReady($creds)) {
            $out[] = self::GATEWAY_BANK;
        }

        return $out;
    }

    public function isGatewayEnabled(Company $company, string $gatewayId): bool
    {
        return in_array($gatewayId, $this->enabledGatewayIds($company), true);
    }

    /**
     * @return list<array{id: string, label: string, icon: string, ready: bool, description: string}>
     */
    public function clientPaymentMethods(Company $company): array
    {
        $creds = $this->resolvedCredentials($company);
        $methods = [];

        foreach ($this->enabledGatewayIds($company) as $id) {
            $ready = match ($id) {
                self::GATEWAY_STRIPE => $this->stripeReady($creds),
                self::GATEWAY_PAYPAL => $this->paypalReady($creds),
                self::GATEWAY_FLOUCI => $this->flouciReady($creds),
                self::GATEWAY_BANK => $this->bankTransferReady($creds),
                default => false,
            };

            if (! $ready) {
                continue;
            }

            $methods[] = [
                'id' => $id,
                'label' => $this->gatewayLabel($id),
                'icon' => $this->gatewayIcon($id),
                'ready' => true,
                'description' => $this->gatewayDescription($id),
            ];
        }

        return $methods;
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    public function stripeReady(array $creds): bool
    {
        return ! empty($creds['stripe_public_key']) && ! empty($creds['stripe_secret_key']);
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    public function paypalReady(array $creds): bool
    {
        return ! empty($creds['paypal_client_id']) && ! empty($creds['paypal_secret']);
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    public function flouciReady(array $creds): bool
    {
        return ! empty($creds['flouci_public_key']) && ! empty($creds['flouci_secret_key']);
    }

    /**
     * Payment methods available on the public marketplace checkout.
     *
     * @return list<array{value: string, id: string, label: string, icon: string, description: string}>
     */
    public function marketplaceCheckoutMethods(): array
    {
        $creds = $this->platformCredentials();
        $methods = [];

        if ($this->stripeReady($creds)) {
            $methods[] = [
                'value' => 'stripe',
                'id' => self::GATEWAY_STRIPE,
                'label' => $this->gatewayLabel(self::GATEWAY_STRIPE),
                'icon' => $this->gatewayIcon(self::GATEWAY_STRIPE),
                'description' => $this->gatewayDescription(self::GATEWAY_STRIPE),
            ];
        }

        if ($this->bankTransferReady($creds)) {
            $methods[] = [
                'value' => 'bank',
                'id' => self::GATEWAY_BANK,
                'label' => $this->gatewayLabel(self::GATEWAY_BANK),
                'icon' => $this->gatewayIcon(self::GATEWAY_BANK),
                'description' => $this->gatewayDescription(self::GATEWAY_BANK),
            ];
        }

        return $methods;
    }

    /**
     * @return array{holder: string, bank_name: string, rib: string, bic: string, extra: string}
     */
    public function bankTransferDetails(?array $creds = null): array
    {
        $creds ??= $this->platformCredentials();

        return [
            'holder' => trim((string) ($creds['bank_account_holder'] ?? '')),
            'bank_name' => trim((string) ($creds['bank_name'] ?? '')),
            'rib' => trim((string) ($creds['bank_rib'] ?? '')),
            'bic' => trim((string) ($creds['bank_bic'] ?? '')),
            'extra' => trim((string) ($creds['bank_instructions'] ?? '')),
        ];
    }

    public function bankTransferInstructions(?array $creds = null): ?string
    {
        $creds ??= $this->platformCredentials();

        $lines = [];

        $holder = trim((string) ($creds['bank_account_holder'] ?? ''));
        if ($holder !== '') {
            $lines[] = __('admin_payment_bank_account_holder').': '.$holder;
        }

        $bankName = trim((string) ($creds['bank_name'] ?? ''));
        if ($bankName !== '') {
            $lines[] = __('admin_payment_bank_name').': '.$bankName;
        }

        $rib = trim((string) ($creds['bank_rib'] ?? ''));
        if ($rib !== '') {
            $lines[] = __('admin_payment_bank_rib').': '.$rib;
        }

        $bic = trim((string) ($creds['bank_bic'] ?? ''));
        if ($bic !== '') {
            $lines[] = __('admin_payment_bank_bic').': '.$bic;
        }

        $extra = trim((string) ($creds['bank_instructions'] ?? ''));
        if ($extra !== '') {
            if ($lines !== []) {
                $lines[] = '';
            }
            $lines[] = $extra;
        }

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $creds
     */
    public function bankTransferReady(array $creds): bool
    {
        return $this->bankTransferInstructions($creds) !== null;
    }

    public function gatewayLabel(string $id): string
    {
        return match ($id) {
            self::GATEWAY_STRIPE => __('Pay with card (Stripe)'),
            self::GATEWAY_PAYPAL => __('Pay with PayPal'),
            self::GATEWAY_FLOUCI => __('Pay with Flouci'),
            self::GATEWAY_BANK => __('Bank transfer'),
            default => $id,
        };
    }

    public function gatewayIcon(string $id): string
    {
        return match ($id) {
            self::GATEWAY_STRIPE => 'fa-brands fa-stripe',
            self::GATEWAY_PAYPAL => 'fa-brands fa-paypal',
            self::GATEWAY_FLOUCI => 'fa-solid fa-credit-card',
            self::GATEWAY_BANK => 'fa-solid fa-building-columns',
            default => 'fa-solid fa-money-bill',
        };
    }

    public function gatewayDescription(string $id): string
    {
        return match ($id) {
            self::GATEWAY_STRIPE => __('portal_pay_method_stripe_help'),
            self::GATEWAY_PAYPAL => __('portal_pay_method_paypal_help'),
            self::GATEWAY_FLOUCI => __('portal_pay_method_flouci_help'),
            self::GATEWAY_BANK => __('portal_pay_method_bank_help'),
            default => '',
        };
    }
}
