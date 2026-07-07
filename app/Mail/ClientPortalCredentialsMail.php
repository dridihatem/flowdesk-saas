<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientPortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public Company $company,
        public string $plainPassword,
    ) {}

    public function build(): self
    {
        return $this
            ->subject(__('client_credentials_mail_subject', ['company' => $this->company->name]))
            ->view('emails.client-portal-credentials');
    }
}
