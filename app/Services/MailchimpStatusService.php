<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MailchimpStatusService
{
    public function ping(string $apiKey, string $serverPrefix): bool
    {
        $serverPrefix = ltrim($serverPrefix, 'https://');
        if (str_contains($serverPrefix, '.')) {
            $serverPrefix = Str::before($serverPrefix, '.api.mailchimp.com');
        }
        if ($apiKey === '' || $serverPrefix === '') {
            return false;
        }
        $url = 'https://'.$serverPrefix.'.api.mailchimp.com/3.0/ping';
        $response = Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(15)
            ->get($url);

        return $response->successful() && (string) $response->body() !== '';
    }
}
