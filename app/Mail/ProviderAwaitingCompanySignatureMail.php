<?php

namespace App\Mail;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProviderAwaitingCompanySignatureMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Provider $provider,
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        $company = $this->provider->company;

        return $this
            ->subject(__('Provider :name signed — your signature needed', ['name' => $this->provider->name]))
            ->view('emails.provider-awaiting-company-signature');
    }
}
