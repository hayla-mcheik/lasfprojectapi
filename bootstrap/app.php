<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // apiPrefix: '' 
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Register your middleware aliases here
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            // ✅ Add the new Army permission gatekeeper
            'army_access' => \App\Http\Middleware\ArmyAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * ✅ FIX: "The GET method is not supported for route api/login"
         * This forces Laravel to return a 401 JSON error instead of 
         * trying to redirect to a login page when a token is invalid.
         */
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });
    })
    ->create();