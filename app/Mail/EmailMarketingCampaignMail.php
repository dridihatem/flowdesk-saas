<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailMarketingCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $htmlBody,
        public string $fromAddress,
        public string $fromName,
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        return $this->from($this->fromAddress, $this->fromName)
            ->subject($this->emailSubject)
            ->html($this->htmlBody);
    }
}
