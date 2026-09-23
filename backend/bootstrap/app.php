<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            SetLocaleFromRequest::class,
        ]);

        // Applied globally (API, web, and the Filament admin panel) so every
        // response — not just the JSON API — gets baseline hardening.
        $middleware->append(SecurityHeaders::class);

        // This app has no web login route — it's an API consumed by a
        // separate frontend — so unauthenticated requests should always get
        // a 401 JSON response instead of Laravel's default redirect to a
        // named "login" route (which doesn't exist here).
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Always return a clean, generic JSON error for API requests — never
        // the framework's file/line/stack-trace payload, regardless of
        // APP_DEBUG. Validation errors are left to Laravel's own handling,
        // which already returns a clean {message, errors} shape.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => 404,
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                $e instanceof TooManyRequestsHttpException => 429,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $message = match (true) {
                $status === 404 => 'Ressource introuvable.',
                $status === 401 => 'Authentification requise.',
                $status === 403 => 'Accès refusé.',
                $status === 429 => 'Trop de requêtes, veuillez réessayer dans un instant.',
                $status >= 500 => 'Une erreur est survenue, veuillez réessayer plus tard.',
                default => $e->getMessage() ?: 'Une erreur est survenue.',
            };

            return response()->json(['message' => $message], $status);
        });
    })->create();
