<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\MarketplaceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketplaceOrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{name: string, url: ?string, attach_path: ?string, attach_name: ?string, size: int}>  $downloads
     * @param  list<string>  $installedModuleNames
     */
    public function __construct(
        public MarketplaceOrder $order,
        public array $downloads,
        public array $installedModuleNames = [],
        public ?Company $company = null,
    ) {}

    public function build(): self
    {
        $mail = $this
            ->subject(__('marketplace_order_paid_mail_subject', ['number' => $this->order->order_number]))
            ->view('emails.marketplace-order-paid');

        foreach ($this->downloads as $download) {
            if (is_string($download['attach_path'] ?? null) && is_string($download['attach_name'] ?? null) && is_file($download['attach_path'])) {
                $mail->attach($download['attach_path'], [
                    'as' => $download['attach_name'],
                    'mime' => 'application/zip',
                ]);
            }
        }

        return $mail;
    }
}
