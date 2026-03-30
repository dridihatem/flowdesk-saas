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
            return $next($request);
        }

        if ($request->user()->company_id === null) {
            abort(403);
        }

        if ((string) $request->user()->company_id !== (string) $current->id) {
            abort(403);
        }

        return $next($request);
    }
}
