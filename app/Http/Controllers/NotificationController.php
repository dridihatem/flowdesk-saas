<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $logs = AuditLog::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('logs'));
    }
}
