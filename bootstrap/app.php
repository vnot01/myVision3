<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerifyRvmApiKey;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        // Daftarkan alias untuk middleware
        $middleware->alias([
            'auth.rvm' => VerifyRvmApiKey::class,
            'role.admin_operator' => \App\Http\Middleware\CheckAdminOperatorRole::class,
            // ... alias lain ...
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();