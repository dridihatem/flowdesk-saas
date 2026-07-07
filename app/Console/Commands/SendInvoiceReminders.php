<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceMailService;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';

    protected $description = 'Email reminders for overdue unpaid invoices (one per invoice).';

    public function handle(InvoiceMailService $mailer): int
    {
        $q = Invoice::query()->withoutGlobalScope('tenant')
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereNull('reminder_sent_at')
            ->with(['client', 'company']);

        foreach ($q->cursor() as $invoice) {
            $company = $invoice->company;
            if ($company === null) {
                continue;
            }

            try {
                $mailer->send($invoice, $company, true);
            } catch (\Throwable $e) {
                $this->warn("Invoice {$invoice->id}: {$e->getMessage()}");

                continue;
            }

            $invoice->update([
                'reminder_sent_at' => now(),
                'status' => $invoice->status === InvoiceStatus::Sent ? InvoiceStatus::Overdue : $invoice->status,
            ]);
        }

        return self::SUCCESS;
    }
}
