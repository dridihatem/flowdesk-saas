<?php

namespace App\Services;

use App\Models\Company;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioSmsTestService
{
    public function __construct(
        private MarketingIntegrationConfigService $config
    ) {}

    public function sendTest(Company $company, string $to, string $message = ''): void
    {
        $c = $this->config->getResolved($company)['twilio'];
        $accountSid = $c['account_sid'] ?? '';
        $token = $c['auth_token'] ?? '';
        $from = $c['from'] ?? '';
        if ($accountSid === '' || $token === '' || $from === '') {
            throw new \RuntimeException(__('twilio_incomplete_in_settings'));
        }
        if ($message === '') {
            $message = __('twilio_test_message');
        }
        $client = new Client($accountSid, $token);
        try {
            $client->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);
        } catch (TwilioException $e) {
            throw new \RuntimeException(__('twilio_send_failed', ['message' => $e->getMessage()]), 0, $e);
        }
    }
}
