<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $documentTemplates
     */
    public function __construct(
        public Invoice $invoice,
        public Company $company,
        public bool $isReminder = false,
        public array $documentTemplates = [],
    ) {
        $this->afterCommit = true;
    }

    public function build(): self
    {
        $num = $this->invoice->number ?? $this->invoice->id;
        $subject = $this->isReminder
            ? __('Reminder: invoice :num — :company', ['num' => $num, 'company' => $this->company->name])
            : __('Invoice :num — :company', ['num' => $num, 'company' => $this->company->name]);

        return $this->subject($subject)->view('emails.invoice-sent');
    }
}
