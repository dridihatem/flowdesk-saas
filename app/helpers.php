<?php

use App\Models\Company;

if (! function_exists('flowdesk_tenant_url')) {
    /**
     * Build a URL for a tenant host when FLOWDESK_TENANT_BASE_DOMAIN is set; otherwise fall back to the central app URL.
     */
    function flowdesk_tenant_url(?Company $company, string $path = '/'): string
    {
        if ($company === null) {
            return url($path);
        }

        $base = config('flowdesk.tenant_base_domain');

        if ($base === null || $base === '') {
            return url($path);
        }

        $root = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($root, PHP_URL_SCHEME) ?: 'https';
        $host = $company->subdomain.'.'.ltrim($base, '.');

        return $scheme.'://'.$host.'/'.ltrim($path, '/');
    }
}
