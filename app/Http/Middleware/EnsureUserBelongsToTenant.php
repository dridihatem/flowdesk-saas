<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $current = app()->bound('currentCompany')
            ? app()->make('currentCompany')
            : null;

        if (! $current instanceof Company) {
            // ResolveTenant runs before the session starts, so its fallback to the
            // signed-in user's company never applies. Bind it here (post-auth) so
            // TenantScope stays active on hosts without a tenant subdomain.
            if ($request->user()->company instanceof Company) {
                app()->instance('currentCompany', $request->user()->company);
            }

            return $next($request);
        }

        $user = $request->user();

        if (! $current->is_enabled && ! $user->hasRole('platform_admin')) {
            abort(403, __('This workspace is disabled. Please contact support.'));
        }

        if ($user->company_id === null) {
            abort(403, __('This workspace URL is for a specific company. Sign in with a workspace team account, or open the main site.'));
        }

        if ((string) $user->company_id === (string) $current->id) {
            return $next($request);
        }

        $company = $user->company;

        if ($company instanceof Company) {
            $target = flowdesk_tenant_url($company, $request->getRequestUri());
            $targetHost = parse_url($target, PHP_URL_HOST);

            if ($targetHost && $targetHost !== $request->getHost()) {
                return redirect()->away($target)
                    ->with('tenant_switch_notice', __('You are signed in to a different workspace. We opened your company URL.'));
            }
        }

        abort(403, __('You do not have access to this workspace.'));
    }
}
