<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/api/workbright-webhook',
            '/api/quickqore/ap-invoices/sync',
            '/api/quickqore/ar-invoices/sync',
            '/api/quickqore/ar-invoices/update',
            '/api/quickqore/ar-invoices/delete',
            '/api/quickqore/ar-payments/sync',
            '/api/quickqore/ar-payments/update',
            '/api/quickqore/ar-payments/delete',
            '/api/quickqore/ar-customers/sync',
            '/api/quickqore/ar-customers/update',
            '/api/quickqore/ar-customers/delete',
            '/api/quickqore/ideal-costs'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
