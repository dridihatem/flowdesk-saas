<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientNote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_if(! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        $client->loadMissing('company');

        $canViewInvoices = $user->can('portal.view_invoices') || $user->can('portal.view_payments');
        $canViewProposals = $user->can('portal.view_proposals');

        $pendingPaymentInvoices = 0;
        $pendingPaymentOutstandingMinor = 0;
        $totalPaymentsMinor = 0;
        $pendingAcceptanceProposals = 0;

        if ($canViewInvoices) {
            $invoiceRows = Invoice::query()
                ->where('client_id', $client->id)
                ->where('status', '!=', InvoiceStatus::Cancelled)
                ->where('amount', '>', 0)
                ->withSum(
                    ['payments as completed_payments_sum' => fn ($query) => $query->where('status', PaymentStatus::Completed)],
                    'amount'
                )
                ->get(['id', 'amount']);

            foreach ($invoiceRows as $invoice) {
                $paidMinor = (int) ($invoice->completed_payments_sum ?? 0);
                $balanceMinor = max(0, (int) $invoice->amount - $paidMinor);
                $totalPaymentsMinor += $paidMinor;

                if ($balanceMinor > 0) {
                    $pendingPaymentInvoices++;
                    $pendingPaymentOutstandingMinor += $balanceMinor;
                }
            }
        }

        if ($canViewProposals) {
            $pendingAcceptanceProposals = $client->proposals()
                ->where('status', ProposalStatus::Sent)
                ->count();
        }

        $currency = flowdesk_normalize_currency_code($client->company?->default_currency);

        $sharedNotes = ClientNote::query()
            ->where('client_id', $client->id)
            ->where('visible_to_client', true)
            ->with(['provider:id,name', 'author:id,name'])
            ->orderByDesc('noted_on')
            ->limit(20)
            ->get();

        return view('portal.dashboard', compact(
            'client',
            'canViewInvoices',
            'canViewProposals',
            'pendingPaymentInvoices',
            'pendingPaymentOutstandingMinor',
            'totalPaymentsMinor',
            'pendingAcceptanceProposals',
            'currency',
            'sharedNotes',
        ));
    }
}
