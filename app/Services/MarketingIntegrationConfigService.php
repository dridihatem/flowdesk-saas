<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class MarketingIntegrationConfigService
{
    public const DEFAULT_CAMPAIGN_EMAIL = 'app_default';

    public const CAMPAIGN_EMAIL_APP = 'app_default';

    public const CAMPAIGN_EMAIL_TENANT_SMTP = 'tenant_smtp';

    public const CAMPAIGN_EMAIL_SENDGRID = 'sendgrid';

    /**
     * @return array{
     *   campaign_email: string,
     *   sendgrid: array{api_key: string|null},
     *   mailchimp: array{api_key: string|null, server_prefix: string|null, list_id: string|null},
     *   twilio: array{account_sid: string|null, auth_token: string|null, from: string|null}
     * }
     */
    public function getResolved(Company $company): array
    {
        $row = $company->settings;
        $raw = is_array($row?->integration_channels) ? $row->integration_channels : [];
        $campaignEmail = (string) ($raw['campaign_email'] ?? self::DEFAULT_CAMPAIGN_EMAIL);
        if (! in_array($campaignEmail, [self::CAMPAIGN_EMAIL_APP, self::CAMPAIGN_EMAIL_TENANT_SMTP, self::CAMPAIGN_EMAIL_SENDGRID], true)) {
            $campaignEmail = self::DEFAULT_CAMPAIGN_EMAIL;
        }

        $sendgrid = $raw['sendgrid'] ?? [];
        if (! is_array($sendgrid)) {
            $sendgrid = [];
        }
        $mailchimp = $raw['mailchimp'] ?? [];
        if (! is_array($mailchimp)) {
            $mailchimp = [];
        }
        $twilio = $raw['twilio'] ?? [];
        if (! is_array($twilio)) {
            $twilio = [];
        }

        return [
            'campaign_email' => $campaignEmail,
            'sendgrid' => [
                'api_key' => $this->decryptString($sendgrid['api_key_enc'] ?? null),
            ],
            'mailchimp' => [
                'api_key' => $this->decryptString($mailchimp['api_key_enc'] ?? null),
                'server_prefix' => isset($mailchimp['server_prefix']) && is_string($mailchimp['server_prefix'])
                    ? trim($mailchimp['server_prefix'])
                    : null,
                'list_id' => isset($mailchimp['list_id']) && is_string($mailchimp['list_id']) ? trim($mailchimp['list_id']) : null,
            ],
            'twilio' => [
                'account_sid' => isset($twilio['account_sid']) && is_string($twilio['account_sid']) ? trim($twilio['account_sid']) : null,
                'auth_token' => $this->decryptString($twilio['auth_token_enc'] ?? null),
                'from' => isset($twilio['from']) && is_string($twilio['from']) ? trim($twilio['from']) : null,
            ],
        ];
    }

    public function hasSendgrid(Company $company): bool
    {
        $k = $this->getResolved($company)['sendgrid']['api_key'] ?? null;

        return is_string($k) && $k !== '';
    }

    public function hasTwilio(Company $company): bool
    {
        $c = $this->getResolved($company)['twilio'];

        return is_string($c['account_sid'] ?? null) && $c['account_sid'] !== ''
            && is_string($c['auth_token'] ?? null) && $c['auth_token'] !== ''
            && is_string($c['from'] ?? null) && $c['from'] !== '';
    }

    public function hasMailchimp(Company $company): bool
    {
        $m = $this->getResolved($company)['mailchimp'];

        return is_string($m['api_key'] ?? null) && $m['api_key'] !== ''
            && is_string($m['server_prefix'] ?? null) && $m['server_prefix'] !== '';
    }

    public function saveFromRequest(Company $company, array $data): void
    {
        $current = is_array($company->settings?->integration_channels) ? $company->settings->integration_channels : [];
        if (! is_array($current)) {
            $current = [];
        }

        $out = $current;
        if (isset($data['campaign_email']) && in_array(
            $data['campaign_email'],
            [self::CAMPAIGN_EMAIL_APP, self::CAMPAIGN_EMAIL_TENANT_SMTP, self::CAMPAIGN_EMAIL_SENDGRID],
            true
        )) {
            $out['campaign_email'] = $data['campaign_email'];
        }

        if (array_key_exists('sendgrid_api_key', $data) || ! empty($data['clear_sendgrid_api_key'] ?? null)) {
            $out['sendgrid'] = $out['sendgrid'] ?? [];
            if (! is_array($out['sendgrid'])) {
                $out['sendgrid'] = [];
            }
            if (is_string($data['sendgrid_api_key'] ?? null) && $data['sendgrid_api_key'] !== '' && $data['sendgrid_api_key'] !== '********') {
                $out['sendgrid']['api_key_enc'] = Crypt::encryptString($data['sendgrid_api_key']);
            } elseif (! empty($data['clear_sendgrid_api_key'] ?? null)) {
                $out['sendgrid']['api_key_enc'] = null;
            }
        }

        if (array_key_exists('mailchimp_server_prefix', $data) || array_key_exists('mailchimp_list_id', $data) || array_key_exists('mailchimp_api_key', $data)
            || ! empty($data['clear_mailchimp_api_key'] ?? null)
        ) {
            $out['mailchimp'] = $out['mailchimp'] ?? [];
            if (! is_array($out['mailchimp'])) {
                $out['mailchimp'] = [];
            }
            if (is_string($data['mailchimp_api_key'] ?? null) && $data['mailchimp_api_key'] !== '' && $data['mailchimp_api_key'] !== '********') {
                $out['mailchimp']['api_key_enc'] = Crypt::encryptString($data['mailchimp_api_key']);
            } elseif (! empty($data['clear_mailchimp_api_key'] ?? null)) {
                $out['mailchimp']['api_key_enc'] = null;
            }
            if (is_string($data['mailchimp_server_prefix'] ?? null)) {
                $prefix = trim($data['mailchimp_server_prefix']);
                if ($prefix !== '' && str_starts_with($prefix, 'http') && preg_match('#https?://([^.]+)\.api\.mailchimp\.com#i', $prefix, $m)) {
                    $out['mailchimp']['server_prefix'] = $m[1];
                } elseif ($prefix !== '' && str_contains($prefix, '.')) {
                    $out['mailchimp']['server_prefix'] = Str::before($prefix, '.api.mailchimp.com');
                } elseif ($prefix !== '') {
                    $out['mailchimp']['server_prefix'] = $prefix;
                } else {
                    $out['mailchimp']['server_prefix'] = null;
                }
            }
            if (array_key_exists('mailchimp_list_id', $data)) {
                $out['mailchimp']['list_id'] = is_string($data['mailchimp_list_id']) && $data['mailchimp_list_id'] !== '' ? $data['mailchimp_list_id'] : null;
            }
        }

        if (array_key_exists('twilio_account_sid', $data) || array_key_exists('twilio_from', $data) || array_key_exists('twilio_auth_token', $data) || ! empty($data['clear_twilio_token'] ?? null)) {
            $out['twilio'] = $out['twilio'] ?? [];
            if (! is_array($out['twilio'])) {
                $out['twilio'] = [];
            }
            if (array_key_exists('twilio_account_sid', $data)) {
                $out['twilio']['account_sid'] = is_string($data['twilio_account_sid']) && $data['twilio_account_sid'] !== '' ? $data['twilio_account_sid'] : null;
            }
            if (array_key_exists('twilio_from', $data)) {
                $out['twilio']['from'] = is_string($data['twilio_from']) && $data['twilio_from'] !== '' ? $data['twilio_from'] : null;
            }
            if (is_string($data['twilio_auth_token'] ?? null) && $data['twilio_auth_token'] !== '' && $data['twilio_auth_token'] !== '********') {
                $out['twilio']['auth_token_enc'] = Crypt::encryptString($data['twilio_auth_token']);
            } elseif (! empty($data['clear_twilio_token'] ?? null)) {
                $out['twilio']['auth_token_enc'] = null;
            }
        }

        $settings = $company->settings()->firstOrCreate();
        $settings->update(['integration_channels' => $out]);
    }

    public function toFormArray(Company $company): array
    {
        $row = $company->settings;
        $raw = is_array($row?->integration_channels) ? $row->integration_channels : [];
        $r = $this->getResolved($company);

        return [
            'campaign_email' => $r['campaign_email'] ?? self::DEFAULT_CAMPAIGN_EMAIL,
            'sendgrid_api_key' => $r['sendgrid']['api_key'] ? '********' : null,
            'has_sendgrid_key' => $r['sendgrid']['api_key'] !== null && $r['sendgrid']['api_key'] !== '',
            'mailchimp_api_key' => $r['mailchimp']['api_key'] ? '********' : null,
            'has_mailchimp_key' => $r['mailchimp']['api_key'] !== null,
            'mailchimp_server_prefix' => $r['mailchimp']['server_prefix'] ?? '',
            'mailchimp_list_id' => $r['mailchimp']['list_id'] ?? '',
            'twilio_account_sid' => $r['twilio']['account_sid'] ?? '',
            'twilio_from' => $r['twilio']['from'] ?? '',
            'has_twilio_token' => ! empty($raw['twilio']['auth_token_enc'] ?? null),
        ];
    }

    private function decryptString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }
}
