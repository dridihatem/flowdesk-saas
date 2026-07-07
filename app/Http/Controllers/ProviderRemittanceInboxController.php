<?php

namespace App\Http\Controllers;

use App\Enums\ProviderRemittanceStatus;
use App\Models\ProviderRemittanceRequest;
use App\Services\ProviderCommissionBalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderRemittanceInboxController extends Controller
{
    public function index(Request $request, ProviderCommissionBalanceService $commissions): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $status = $request->string('status')->trim()->toString();
        $allowedStatuses = array_column(ProviderRemittanceStatus::cases(), 'value');
        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $query = ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with(['provider', 'reviewedBy'])
            ->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();
        $summary = $commissions->companySummary($company);

        $pendingCount = ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', ProviderRemittanceStatus::Pending)
            ->count();

        return view('providers.remittance-requests.index', [
            'requests' => $requests,
            'summary' => $summary,
            'status' => $status,
            'pendingCount' => $pendingCount,
        ]);
    }
}
