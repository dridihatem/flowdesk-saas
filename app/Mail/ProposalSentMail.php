<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProposalSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $documentTemplates
     */
    public function __construct(
        public Proposal $proposal,
        public Company $company,
        public array $documentTemplates = [],
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        $num = $this->proposal->number ?? $this->proposal->id;

        return $this
            ->subject(__('Quote :num — :company', ['num' => $num, 'company' => $this->company->name]))
            ->view('emails.proposal-sent');
    }
}
