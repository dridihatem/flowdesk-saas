<?php

namespace App\Http\Middleware;

use App\Enums\ProviderPartnershipStatus;
use App\Models\Provider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderPartnershipActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('business_provider')) {
            return $next($request);
        }

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $provider || $provider->partnership_status === ProviderPartnershipStatus::Active) {
            return $next($request);
        }

        if ($request->routeIs('provider.partnership.*', 'provider.dashboard')) {
            return $next($request);
        }

        return redirect()->route('provider.dashboard');
    }
}
