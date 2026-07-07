<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        $invoices = Invoice::query()
            ->where('client_id', $client->id)
            ->with(['payments' => fn ($q) => $q->latest()])
            ->latest()
            ->paginate(15);

        return view('portal.payments.index', compact('client', 'invoices'));
    }
}
