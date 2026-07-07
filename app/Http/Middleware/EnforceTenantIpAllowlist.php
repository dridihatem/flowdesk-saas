<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;

        if ($company === null) {
            return $next($request);
        }

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $ips = $settings?->security['allowed_ips'] ?? null;

        if (! is_array($ips) || $ips === []) {
            return $next($request);
        }

        $ip = $request->ip();
        if (! in_array($ip, $ips, true)) {
            abort(403, __('Access denied from this IP address.'));
        }

        return $next($request);
    }
}
