<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central application hostnames
    |--------------------------------------------------------------------------
    |
    | Requests whose Host header matches one of these values are treated as the
    | main (non-tenant) site: marketing, registration, billing portal, etc.
    |
    */
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FLOWDESK_CENTRAL_DOMAINS', '127.0.0.1,localhost'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Tenant base domain
    |--------------------------------------------------------------------------
    |
    | When set (e.g. flowdesk-saas.com), the first label is the tenant subdomain.
    | Leave null for local development where you rely on *.test hosts only.
    |
    */
    'tenant_base_domain' => env('FLOWDESK_TENANT_BASE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Wildcard DNS (production)
    |--------------------------------------------------------------------------
    |
    | Point *.flowdesk-saas.com to your app server, set FLOWDESK_TENANT_BASE_DOMAIN
    | to flowdesk-saas.com, and set SESSION_DOMAIN=.flowdesk-saas.com so session
    | cookies are shared between app and tenant hosts when you need SSO.
    |
    */

];
