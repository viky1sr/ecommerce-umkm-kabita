<?php

use App\Http\Middleware\ForceApiJson;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller' => \App\Http\Middleware\EnsureSeller::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'buyer' => \App\Http\Middleware\EnsureBuyer::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
        ]);

        $middleware->api(append: [
            ForceApiJson::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
