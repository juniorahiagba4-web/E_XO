<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    private const SUPPORTED_LOCALES = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('locale') ?? $request->getPreferredLanguage(self::SUPPORTED_LOCALES);

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
