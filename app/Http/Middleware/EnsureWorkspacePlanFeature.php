<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature = ''): Response
    {
        if ($feature === '') {
            return $next($request);
        }

        $user = $request->user();
        if (! $user?->company || ! $user->hasAnyRole(['company_admin', 'team_member'])) {
            return $next($request);
        }

        $gates = $request->attributes->get('flowdeskPlanGates');
        if (is_array($gates) && array_key_exists($feature, $gates)) {
            if (! $gates[$feature]) {
                abort(403, __('plan_feature_not_included'));
            }

            return $next($request);
        }

        if (! app(PlanLimitService::class)->isFeatureEnabled($user->company, $feature)) {
            abort(403, __('plan_feature_not_included'));
        }

        return $next($request);
    }
}
