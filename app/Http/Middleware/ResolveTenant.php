<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = null;

        if ($this->isCentralHost($request->getHost())) {
            $company = null;
        } else {
            $subdomain = $this->extractSubdomain($request->getHost(), config('flowdesk.tenant_base_domain'));

            if ($subdomain === null || $subdomain === '' || $subdomain === 'www') {
                $company = null;
            } else {
                $resolved = Company::query()->where('subdomain', $subdomain)->first();
                if (! $resolved) {
                    abort(404, __('Tenant not found.'));
                }
                $company = $resolved;
            }
        }

        // Host without tenant (localhost, central app, etc.): scope models to the signed-in workspace so route binding and queries stay consistent.
        if (! $company instanceof Company && $request->user()?->company instanceof Company) {
            $company = $request->user()->company;
        }

        app()->instance('currentCompany', $company);

        return $next($request);
    }

    private function isCentralHost(string $host): bool
    {
        $central = config('flowdesk.central_domains', []);

        return in_array($host, $central, true);
    }

    private function extractSubdomain(string $host, ?string $baseDomain): ?string
    {
        if ($baseDomain !== null && $baseDomain !== '' && str_ends_with($host, $baseDomain)) {
            $prefix = substr($host, 0, strlen($host) - strlen($baseDomain));

            return trim($prefix, '.') ?: null;
        }

        $parts = explode('.', $host);

        if (count($parts) < 2) {
            return null;
        }

        return $parts[0];
    }
}
