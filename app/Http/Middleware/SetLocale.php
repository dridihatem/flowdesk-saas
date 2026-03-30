<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('flowdesk.locales', ['en']);

        if (session()->has('locale') && in_array(session('locale'), $locales, true)) {
            app()->setLocale(session('locale'));
        } elseif ($request->user()?->locale && in_array($request->user()->locale, $locales, true)) {
            app()->setLocale($request->user()->locale);
        } else {
            /** @var Company|null $company */
            $company = app()->bound('currentCompany') ? app()->make('currentCompany') : null;
            if ($company instanceof Company && $company->default_locale) {
                app()->setLocale($company->default_locale);
            }
        }

        return $next($request);
    }
}
