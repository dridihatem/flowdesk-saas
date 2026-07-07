<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyNoticeController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        AuditLog::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'user_id' => $request->user()?->id,
            'action' => 'platform_notice',
            'auditable_type' => Company::class,
            'auditable_id' => (string) $company->id,
            'properties' => [
                'message' => $data['message'],
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.companies.show', $company)->with('status', __('Notice sent to company activity feed.'));
    }
}
