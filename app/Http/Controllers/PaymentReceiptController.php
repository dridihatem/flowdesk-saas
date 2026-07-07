<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function __invoke(Request $request, Payment $payment): StreamedResponse
    {
        $user = $request->user();
        abort_if(! $user, 403);
        abort_if($payment->receipt_path === null || $payment->receipt_path === '', 404);
        abort_if(! Storage::disk('local')->exists($payment->receipt_path), 404);

        if ($user->hasRole('client')) {
            $client = $user->clientProfile;
            abort_if(! $client, 403);
            abort_if((string) $payment->company_id !== (string) $client->company_id, 403);
            $payment->loadMissing('invoice');
            abort_if((string) $payment->invoice?->client_id !== (string) $client->id, 403);
        } elseif ($user->hasAnyRole(['company_admin', 'team_member'])) {
            abort_if((string) $payment->company_id !== (string) $user->company_id, 403);
        } else {
            abort(403);
        }

        $filename = 'payment-receipt-'.($payment->id).'.'.pathinfo($payment->receipt_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($payment->receipt_path, $filename);
    }
}
