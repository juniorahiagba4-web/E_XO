<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel is always in French (buttons, filters, dates, messages),
 * whatever the browser language, while the storefront API keeps following
 * the visitor's language.
 */
class UseFrenchInAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('fr');
        Carbon::setLocale('fr');

        return $next($request);
    }
}
