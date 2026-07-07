<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\LocaleDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private LocaleDetectionService $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $supported = $this->locales->supportedLocales();

        if (session()->has('locale') && in_array(session('locale'), $supported, true)) {
            app()->setLocale(session('locale'));

            return $next($request);
        }

        if ($request->user()?->locale && in_array($request->user()->locale, $supported, true)) {
            app()->setLocale($request->user()->locale);

            return $next($request);
        }

        /** @var Company|null $company */
        $company = app()->bound('currentCompany') ? app()->make('currentCompany') : null;
        if ($company instanceof Company && $company->default_locale && in_array($company->default_locale, $supported, true)) {
            app()->setLocale($company->default_locale);

            return $next($request);
        }

        if (! session()->has('locale_auto_pinned')) {
            $detected = $this->locales->detectFromRequest($request);
            if ($detected !== null) {
                session([
                    'locale' => $detected,
                    'locale_auto_pinned' => true,
                ]);
                app()->setLocale($detected);
            }
        }

        return $next($request);
    }
}
