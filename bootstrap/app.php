<?php

use App\Http\Middleware\EnforceTenantIpAllowlist;
use App\Http\Middleware\EnsureClientPortalScope;
use App\Http\Middleware\EnsurePendingTwoFactorLogin;
use App\Http\Middleware\EnsureProviderPartnershipActive;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\EnsureWorkspacePlanFeature;
use App\Http\Middleware\EnsureWorkspaceStaffScope;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShareWorkspacePlanContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResolveTenant::class,
        ]);

        $middleware->api(prepend: [
            ResolveTenant::class,
        ]);

        // SetLocale must run after StartSession (session-based locale + user preference).
        $middleware->web(append: [
            SetLocale::class,
            ShareWorkspacePlanContext::class,
            EnforceTenantIpAllowlist::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/flouci',
        ]);

        $middleware->alias([
            'tenant.match' => EnsureUserBelongsToTenant::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'pending.two-factor' => EnsurePendingTwoFactorLogin::class,
            'client.portal' => EnsureClientPortalScope::class,
            'workspace.staff' => EnsureWorkspaceStaffScope::class,
            'plan.feature' => EnsureWorkspacePlanFeature::class,
            'provider.partnership.active' => EnsureProviderPartnershipActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
