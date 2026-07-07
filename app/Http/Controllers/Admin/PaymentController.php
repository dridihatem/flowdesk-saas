<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->withoutGlobalScopes()
            ->with(['company', 'invoice'])
            ->latest()
            ->paginate(30);

        return view('admin.payments.index', compact('payments'));
    }
}
