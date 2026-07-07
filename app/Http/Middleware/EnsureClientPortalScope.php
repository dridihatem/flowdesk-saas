<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientPortalScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('client')) {
            return $next($request);
        }

        if ($user->hasAnyRole(['company_admin', 'team_member', 'business_provider'])) {
            return $next($request);
        }

        if ($request->routeIs([
            'portal.dashboard',
            'portal.client-account-requests.create',
            'portal.client-account-requests.store',
            'portal.projects.*',
            'portal.proposals.*',
            'portal.invoices.*',
            'portal.payments.*',
            'portal.quote-requests.*',
            'portal.calendar',
            'portal.calendar.preview',
            'chat.*',
            'tickets.*',
            'notifications.*',
            'profile.*',
            'verification.*',
            'two-factor.*',
            'logout',
            'locale.update',
        ])) {
            return $next($request);
        }

        return redirect()->route('portal.dashboard');
    }
}
