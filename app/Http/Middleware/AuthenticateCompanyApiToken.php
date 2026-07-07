<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCompanyApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;

        if ($company === null) {
            abort(404, __('Tenant not found.'));
        }

        $token = $request->bearerToken();

        if ($token === null || $token === '' || ! $company->apiTokenMatches($token)) {
            abort(401, __('Invalid or missing API token.'));
        }

        return $next($request);
    }
}
