<?php

namespace App\Services;

use App\Mail\ClientPortalCredentialsMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class ClientCredentialsMailService
{
    /** Sends the portal credentials to the client, using the workspace SMTP when configured. */
    public function send(Client $client, Company $company, string $plainPassword): void
    {
        $clientEmail = $client->email;
        if ($clientEmail === null || $clientEmail === '') {
            throw new \RuntimeException(__('Client has no email address.'));
        }

        $mailable = new ClientPortalCredentialsMail($client, $company, $plainPassword);

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $smtp = $settings?->smtp;

        if (is_array($smtp) && ! empty($smtp['host'])) {
            Config::set('mail.mailers.flowdesk_tenant', [
                'transport' => 'smtp',
                'host' => $smtp['host'],
                'port' => (int) ($smtp['port'] ?? 587),
                'encryption' => $smtp['encryption'] ?? 'tls',
                'username' => $smtp['username'] ?? null,
                'password' => $smtp['password'] ?? null,
                'timeout' => null,
            ]);
            $fromAddress = $smtp['from_address'] ?? config('mail.from.address');
            $fromName = $smtp['from_name'] ?? config('mail.from.name');
            $mailable->from($fromAddress, $fromName);
            Mail::mailer('flowdesk_tenant')->to($clientEmail)->send($mailable);

            return;
        }

        Mail::to($clientEmail)->send($mailable);
    }
}
