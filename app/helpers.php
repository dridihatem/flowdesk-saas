<?php

use App\Models\Company;
use App\Models\InstalledModule;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePaymentGatewayService;
use App\Services\ModuleTranslationService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

if (! function_exists('flowdesk_post_login_redirect')) {
    /**
     * Web route name or path for the user after login / 2FA (company vs provider vs platform admin).
     */
    function flowdesk_post_login_redirect(User $user): string
    {
        if ($user->hasRole('platform_admin')) {
            return route('admin.dashboard', absolute: false);
        }

        if ($user->hasRole('business_provider')) {
            return route('provider.dashboard', absolute: false);
        }

        if ($user->hasRole('client')) {
            return route('portal.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }
}

if (! function_exists('flowdesk_invoice_payment_url')) {
    /**
     * Client portal URL where the invoice can be viewed and paid.
     */
    function flowdesk_invoice_payment_url(Invoice $invoice): string
    {
        $invoice->loadMissing('company');

        return flowdesk_tenant_url(
            $invoice->company,
            route('portal.invoices.show', $invoice, false),
        );
    }
}

if (! function_exists('flowdesk_invoice_payment_qr')) {
    /**
     * Payment QR for an invoice with an outstanding balance (PDF + web).
     *
     * @return array{url: string, data_uri: string, balance_minor: int}|null
     */
    function flowdesk_invoice_payment_qr(Invoice $invoice): ?array
    {
        $invoice->loadMissing('company');

        $completedTotal = (int) $invoice->completedPaymentsTotalMinor();
        $balanceMinor = max(0, (int) $invoice->amount - $completedTotal);

        if ($balanceMinor <= 0) {
            return null;
        }

        $url = flowdesk_invoice_payment_url($invoice);

        return [
            'url' => $url,
            'data_uri' => app(\App\Services\QrCodeService::class)->svgDataUri($url),
            'balance_minor' => $balanceMinor,
        ];
    }
}

if (! function_exists('flowdesk_tenant_url')) {
    /**
     * Build a URL for a tenant host when FLOWDESK_TENANT_BASE_DOMAIN is set; otherwise fall back to the central app URL.
     */
    function flowdesk_tenant_url(?Company $company, string $path = '/'): string
    {
        if ($company === null) {
            return url($path);
        }

        $base = config('flowdesk.tenant_base_domain');

        if ($base === null || $base === '') {
            return url($path);
        }

        $root = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($root, PHP_URL_SCHEME) ?: 'https';
        $host = $company->subdomain.'.'.ltrim($base, '.');

        return $scheme.'://'.$host.'/'.ltrim($path, '/');
    }
}

if (! function_exists('flowdesk_public_site_url')) {
    /**
     * Absolute URL on the central app host (marketing, legal, SEO pages).
     * Use from tenant workspaces so links resolve to the public site, not the tenant subdomain.
     */
    function flowdesk_public_site_url(string $path = '/'): string
    {
        $root = rtrim((string) config('app.url'), '/');
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $root.'/' : $root.$path;
    }
}

if (! function_exists('flowdesk_currency_select_options')) {
    /**
     * Map of ISO 4217 code => label for select fields. Merges legacy or contextual codes (e.g. current document currency).
     *
     * @return array<string, string>
     */
    function flowdesk_currency_select_options(?string ...$alsoInclude): array
    {
        $base = config('flowdesk.supported_currencies', ['USD']);
        $base = is_array($base) ? $base : ['USD'];
        $labels = config('flowdesk.currency_labels', []);
        $labels = is_array($labels) ? $labels : [];

        $codes = [];
        foreach ($base as $c) {
            $u = strtoupper(trim((string) $c));
            if (strlen($u) === 3) {
                $codes[$u] = true;
            }
        }
        foreach ($alsoInclude as $c) {
            if ($c === null || $c === '') {
                continue;
            }
            $u = strtoupper(trim($c));
            if (strlen($u) === 3) {
                $codes[$u] = true;
            }
        }
        ksort($codes);
        $out = [];
        foreach (array_keys($codes) as $code) {
            $out[$code] = $labels[$code] ?? $code;
        }

        return $out;
    }
}

if (! function_exists('flowdesk_invoice_payment_credentials')) {
    /**
     * Platform keys merged with company overrides when a company is provided.
     *
     * @return array<string, mixed>
     */
    function flowdesk_invoice_payment_credentials(?Company $company = null): array
    {
        $service = app(InvoicePaymentGatewayService::class);

        if ($company === null) {
            return $service->platformCredentials();
        }

        return $service->resolvedCredentials($company);
    }
}

if (! function_exists('flowdesk_compose_international_phone')) {
    /**
     * Build E.164-style storage string from optional phone country ISO, HQ country ISO, and national digits only.
     */
    function flowdesk_compose_international_phone(?string $phoneCountryIso, ?string $hqCountryIso, ?string $nationalDigits): ?string
    {
        $nat = preg_replace('/\D/', '', (string) $nationalDigits);
        if ($nat === '') {
            return null;
        }
        $iso = $phoneCountryIso ?: $hqCountryIso;
        $iso = $iso !== null && $iso !== '' ? strtoupper($iso) : null;
        $dial = $iso ? (config('flowdesk_country_dial_codes', [])[$iso] ?? null) : null;
        if ($dial !== null && $dial !== '') {
            return '+'.$dial.$nat;
        }

        return $nat;
    }
}

if (! function_exists('flowdesk_currency_rule')) {
    /**
     * Validation rule: currency must be one of supported options, including any merged "also include" codes.
     */
    function flowdesk_currency_rule(?string ...$alsoInclude): In
    {
        return Rule::in(array_keys(flowdesk_currency_select_options(...$alsoInclude)));
    }
}

if (! function_exists('flowdesk_normalize_currency_code')) {
    /**
     * Uppercase ISO 4217 code or USD when missing/invalid.
     */
    function flowdesk_normalize_currency_code(?string $currency): string
    {
        $c = strtoupper(trim((string) ($currency ?? '')));

        return strlen($c) === 3 && ctype_alpha($c) ? $c : 'USD';
    }
}

if (! function_exists('flowdesk_invoice_currency')) {
    /**
     * Currency code for formatting invoice amounts (document currency, else company default).
     */
    function flowdesk_invoice_currency(Invoice $invoice): string
    {
        $c = strtoupper(trim((string) ($invoice->currency ?? '')));
        if (strlen($c) === 3 && ctype_alpha($c)) {
            return $c;
        }
        $invoice->loadMissing('company');

        return flowdesk_normalize_currency_code($invoice->company?->default_currency);
    }
}

if (! function_exists('flowdesk_locale_name')) {
    /** Human-readable locale label for language pickers. */
    function flowdesk_locale_name(string $locale): string
    {
        return match ($locale) {
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'ar' => 'العربية',
            'id' => 'Bahasa Indonesia',
            'hi' => 'हिन्दी',
            default => strtoupper($locale),
        };
    }
}

if (! function_exists('flowdesk_intl_locale')) {
    /** BCP 47 tag for Intl / JS date formatting from app locale. */
    function flowdesk_intl_locale(?string $locale = null): string
    {
        return flowdesk_speech_recognition_locale($locale) ?? 'en-US';
    }
}

if (! function_exists('flowdesk_is_voice_locale_supported')) {
    function flowdesk_is_voice_locale_supported(?string $locale = null): bool
    {
        $base = strtolower(explode('_', str_replace('-', '_', (string) ($locale ?? app()->getLocale())))[0]);

        return in_array($base, config('flowdesk.locales', ['en', 'fr', 'es', 'ar']), true);
    }
}

if (! function_exists('flowdesk_speech_recognition_locale')) {
    /** BCP 47 tag for Web Speech API (STT/TTS), or null when voice is not supported. */
    function flowdesk_speech_recognition_locale(?string $locale = null): ?string
    {
        if (! flowdesk_is_voice_locale_supported($locale)) {
            return null;
        }

        $base = strtolower(explode('_', str_replace('-', '_', (string) ($locale ?? app()->getLocale())))[0]);

        return match ($base) {
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'ar' => 'ar-SA',
            'id' => 'id-ID',
            'hi' => 'hi-IN',
            default => 'en-US',
        };
    }
}

if (! function_exists('flowdesk_locale_amount_separators')) {
    /**
     * @return array{decimal: string, thousands: string}
     */
    function flowdesk_locale_amount_separators(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        if (in_array($locale, ['fr', 'es'], true)) {
            return ['decimal' => ',', 'thousands' => ' '];
        }

        return ['decimal' => '.', 'thousands' => ','];
    }
}

if (! function_exists('flowdesk_currency_minor_scale')) {
    /**
     * Integer scale: stored integer × scale = one major unit (100 = cents, 1 = whole TND dinars).
     */
    function flowdesk_currency_minor_scale(?string $currency): int
    {
        $c = flowdesk_normalize_currency_code($currency);
        $map = config('flowdesk.currency_minor_scale', []);
        $map = is_array($map) ? $map : [];

        return (int) ($map[$c] ?? 100);
    }
}

if (! function_exists('flowdesk_currency_fraction_digits')) {
    /**
     * Max fractional digits when formatting (TND whole dinars = 0; cent currencies = 2).
     */
    function flowdesk_currency_fraction_digits(?string $currency): int
    {
        $c = flowdesk_normalize_currency_code($currency);
        if ($c === 'TND') {
            return 3;
        }

        return flowdesk_currency_minor_scale($currency) <= 1 ? 0 : 2;
    }
}

if (! function_exists('flowdesk_minor_to_major')) {
    /**
     * @return float|int Major-unit amount (may be float when scale > 1)
     */
    function flowdesk_minor_to_major(int $minor, ?string $currency): float
    {
        $scale = flowdesk_currency_minor_scale($currency);

        return $scale > 0 ? $minor / $scale : (float) $minor;
    }
}

if (! function_exists('flowdesk_format_minor')) {
    /**
     * Format stored minor units: integer split (intdiv + remainder), then number_format() on the
     * whole part for thousands/decimals separators; fractional digits come from the remainder (no float divide).
     */
    function flowdesk_format_minor(int $minor, ?string $currency, ?string $decimalSeparator = null, ?string $thousandsSeparator = null, ?string $locale = null): string
    {
        $c = flowdesk_normalize_currency_code($currency);
        $decimals = max(0, flowdesk_currency_fraction_digits($c));
        $scale = flowdesk_currency_minor_scale($c);

        if ($decimalSeparator === null || $thousandsSeparator === null) {
            $sep = flowdesk_locale_amount_separators($locale);
            $decimalSeparator ??= $sep['decimal'];
            $thousandsSeparator ??= $sep['thousands'];
        }

        $negative = $minor < 0;
        $abs = abs($minor);

        if ($scale <= 0) {
            return number_format($negative ? -$abs : $abs, $decimals, $decimalSeparator, $thousandsSeparator);
        }

        $whole = intdiv($abs, $scale);
        $rem = $abs % $scale;

        if ($decimals === 0) {
            return number_format($negative ? -$whole : $whole, 0, $decimalSeparator, $thousandsSeparator);
        }

        $frac = str_pad((string) $rem, $decimals, '0', STR_PAD_LEFT);
        if (strlen($frac) > $decimals) {
            $frac = substr($frac, 0, $decimals);
        }

        $wholePart = number_format($negative ? -$whole : $whole, 0, $decimalSeparator, $thousandsSeparator);

        return $wholePart.$decimalSeparator.$frac;
    }
}

if (! function_exists('flowdesk_major_amount_for_input')) {
    /**
     * Major amount for text inputs: same intdiv/remainder as flowdesk_format_minor; '.' decimal, no thousands; trim trailing zeros in fraction.
     */
    function flowdesk_major_amount_for_input(int $minor, ?string $currency): string
    {
        $c = flowdesk_normalize_currency_code($currency);
        $decimals = max(0, flowdesk_currency_fraction_digits($c));
        $scale = flowdesk_currency_minor_scale($c);
        $abs = abs($minor);
        $negative = $minor < 0;

        if ($scale <= 0) {
            $s = number_format($negative ? -$abs : $abs, $decimals, '.', '');

            return $decimals > 0 && str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
        }

        $whole = intdiv($abs, $scale);
        $rem = $abs % $scale;

        if ($decimals === 0) {
            return (string) ($negative ? -$whole : $whole);
        }

        $frac = str_pad((string) $rem, $decimals, '0', STR_PAD_LEFT);
        if (strlen($frac) > $decimals) {
            $frac = substr($frac, 0, $decimals);
        }
        $s = ($negative ? '-' : '').(string) $whole.'.'.$frac;

        return rtrim(rtrim($s, '0'), '.');
    }
}

if (! function_exists('flowdesk_major_amount_for_locale_input')) {
    /**
     * Major amount for text inputs using the active locale decimal separator.
     */
    function flowdesk_major_amount_for_locale_input(int $minor, ?string $currency, ?string $locale = null): string
    {
        $sep = flowdesk_locale_amount_separators($locale);

        return str_replace('.', $sep['decimal'], flowdesk_major_amount_for_input($minor, $currency));
    }
}

if (! function_exists('flowdesk_minor_percent_of_total')) {
    /**
     * Minor units for a percentage of an invoice total (rounded).
     */
    function flowdesk_minor_percent_of_total(int $totalMinor, float $percent): int
    {
        if ($totalMinor <= 0 || $percent <= 0) {
            return 0;
        }

        return (int) round($totalMinor * ($percent / 100.0));
    }
}

if (! function_exists('flowdesk_decimal_to_minor')) {
    /**
     * Convert a decimal amount string (e.g. "123.45" or "1.250") to integer minor units. Null/empty returns null.
     */
    function flowdesk_decimal_to_minor(?string $value, ?string $currency = null): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $c = flowdesk_normalize_currency_code($currency);
        $scale = flowdesk_currency_minor_scale($c);

        return (int) round((float) str_replace(',', '.', (string) $value) * $scale);
    }
}

if (! function_exists('flowdesk_currency_money_meta_for_js')) {
    /**
     * Per ISO code: { scale, fractionDigits } for invoice line Alpine.js.
     *
     * @return array<string, array{scale: int, fractionDigits: int}>
     */
    function flowdesk_currency_money_meta_for_js(?string ...$alsoInclude): array
    {
        $opts = flowdesk_currency_select_options(...$alsoInclude);
        $out = [];
        foreach (array_keys($opts) as $code) {
            $out[$code] = [
                'scale' => flowdesk_currency_minor_scale($code),
                'fractionDigits' => flowdesk_currency_fraction_digits($code),
            ];
        }

        return $out;
    }
}

if (! function_exists('flowdesk_decimal_to_amount_cents')) {
    /**
     * @deprecated Use flowdesk_decimal_to_minor($value, $currency). Kept for named columns; currency defaults to USD scale.
     */
    function flowdesk_decimal_to_amount_cents(?string $value, ?string $currency = null): ?int
    {
        return flowdesk_decimal_to_minor($value, $currency);
    }
}

if (! function_exists('flowdesk_sanitize_speech_text')) {
    /**
     * Strip markdown/HTML/formatting so TTS reads natural speech, not symbols.
     */
    function flowdesk_sanitize_speech_text(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace('/```[\s\S]*?```/u', ' ', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/u', '$1', $text) ?? $text;
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*\*\*([^*]+)\*\*\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/__([^_]+)__/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*([^*\n]+)\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/_([^_\n]+)_/u', '$1', $text) ?? $text;
        $text = preg_replace('/~~([^~]+)~~/u', '$1', $text) ?? $text;
        $text = preg_replace('/^#{1,6}\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^>\s?/mu', '', $text) ?? $text;
        $text = preg_replace('/^\s*[-*+•]\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^\s*\d+[.)]\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^[-*_]{3,}\s*$/mu', ' ', $text) ?? $text;
        $text = preg_replace('/\.\s+-\s+/u', '. ', $text) ?? $text;
        $text = preg_replace('/[*_`#~|\\[\\]{}]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}

if (! function_exists('flowdesk_pcm16le_to_wav')) {
    /**
     * Wrap raw PCM16LE mono audio in a WAV container (Gemini TTS output).
     */
    function flowdesk_pcm16le_to_wav(string $pcm, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): string
    {
        if ($pcm === '') {
            return '';
        }

        $byteRate = $sampleRate * $channels * ($bitsPerSample / 8);
        $blockAlign = $channels * ($bitsPerSample / 8);
        $dataSize = strlen($pcm);
        $chunkSize = 36 + $dataSize;

        $header = pack('a4Va4', 'RIFF', $chunkSize, 'WAVE');
        $fmt = pack('a4VvvVVvv', 'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample);
        $dataHeader = pack('a4V', 'data', $dataSize);

        return $header.$fmt.$dataHeader.$pcm;
    }
}

if (! function_exists('module_trans')) {
    /**
     * Translate a string from an installed module's lang/{locale}.json pack.
     */
    function module_trans(InstalledModule|string $module, string $key, array $replace = []): string
    {
        if (is_string($module)) {
            return $key;
        }

        return app(ModuleTranslationService::class)->translate($module, $key, $replace);
    }
}

if (! function_exists('flowdesk_vat_percent_for_country')) {
    /**
     * Standard VAT / TVA rate (%) for an ISO 3166-1 alpha-2 country code.
     */
    function flowdesk_vat_percent_for_country(?string $country): float
    {
        if ($country === null || trim($country) === '') {
            return 0.0;
        }

        $map = config('flowdesk_country_vat', []);

        return (float) ($map[strtoupper($country)] ?? 0.0);
    }
}

if (! function_exists('flowdesk_apply_company_billing_vat')) {
    /**
     * Persist workspace default VAT on company billing settings (invoices & quotes).
     */
    function flowdesk_apply_company_billing_vat(Company $company, mixed $vatPercent = null): void
    {
        if ($vatPercent !== null && $vatPercent !== '') {
            $vat = max(0.0, min(100.0, (float) $vatPercent));
        } else {
            $vat = flowdesk_vat_percent_for_country($company->country);
        }

        $settings = app(\App\Services\CompanyThemeService::class)->ensureSettings($company);
        $billing = is_array($settings->billing) ? $settings->billing : [];
        $billing['vat_percent'] = $vat;
        $settings->billing = $billing;
        $settings->save();
    }
}

if (! function_exists('module_label')) {
    /**
     * Module translation with app-lang fallback when the pack has no key.
     */
    function module_label(InstalledModule $module, string $key, string $fallbackLangKey, array $replace = []): string
    {
        $translated = module_trans($module, $key, $replace);
        if ($translated !== $key) {
            return $translated;
        }

        return __($fallbackLangKey, $replace);
    }
}
