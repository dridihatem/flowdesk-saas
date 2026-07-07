<?php

namespace App\Mail;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProviderPartnershipInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Provider $provider,
        public string $termsText,
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        $company = $this->provider->company;

        return $this
            ->subject(__('Sign your partnership with :company', ['company' => $company->name]))
            ->view('emails.provider-partnership-invite');
    }
}
