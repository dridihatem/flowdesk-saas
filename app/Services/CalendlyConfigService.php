<?php

namespace App\Services;

use App\Models\Company;

class CalendlyConfigService
{
    /**
     * @return array{booking_url: string|null, embed_enabled: bool}
     */
    public function get(Company $company): array
    {
        $raw = is_array($company->settings?->integration_channels) ? $company->settings->integration_channels : [];
        $calendly = $raw['calendly'] ?? [];
        if (! is_array($calendly)) {
            $calendly = [];
        }

        $url = isset($calendly['booking_url']) && is_string($calendly['booking_url'])
            ? trim($calendly['booking_url'])
            : '';

        return [
            'booking_url' => $url !== '' ? $this->normalizeBookingUrl($url) : null,
            'embed_enabled' => (bool) ($calendly['embed_enabled'] ?? true),
        ];
    }

    public function hasBookingUrl(Company $company): bool
    {
        return $this->get($company)['booking_url'] !== null;
    }

    public function bookingUrlForDate(string $url, string $isoDate): string
    {
        $url = trim($url);
        if ($url === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'date='.rawurlencode($isoDate.'T09:00:00');
    }

    public function normalizeBookingUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $host = strtolower((string) $parts['host']);
        if ($host === 'calendly.com' || str_ends_with($host, '.calendly.com')) {
            $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

            return 'https://calendly.com'.($path !== '' ? $path : '');
        }

        return $url;
    }

    /**
     * @param  array{booking_url?: string|null, embed_enabled?: bool|null}  $data
     */
    public function save(Company $company, array $data): void
    {
        $current = is_array($company->settings?->integration_channels) ? $company->settings->integration_channels : [];
        if (! is_array($current)) {
            $current = [];
        }

        $out = $current;
        $out['calendly'] = $out['calendly'] ?? [];
        if (! is_array($out['calendly'])) {
            $out['calendly'] = [];
        }

        if (array_key_exists('booking_url', $data)) {
            $rawUrl = is_string($data['booking_url']) ? trim($data['booking_url']) : '';
            $out['calendly']['booking_url'] = $rawUrl !== '' ? $this->normalizeBookingUrl($rawUrl) : null;
        }

        if (array_key_exists('embed_enabled', $data)) {
            $out['calendly']['embed_enabled'] = (bool) $data['embed_enabled'];
        }

        $settings = $company->settings()->firstOrCreate();
        $settings->update(['integration_channels' => $out]);
    }

    /**
     * @return array{booking_url: string, embed_enabled: bool}
     */
    public function toFormArray(Company $company): array
    {
        $resolved = $this->get($company);

        return [
            'booking_url' => $resolved['booking_url'] ?? '',
            'embed_enabled' => $resolved['embed_enabled'],
        ];
    }
}
