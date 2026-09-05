<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // The cookie consent choice is written by the browser, so it arrives
        // unencrypted and would otherwise be discarded as tampered with. It
        // holds no sensitive data, only "accepted" or "rejected".
        $middleware->encryptCookies(except: [
            'cookie_consent',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create()
    ->usePublicPath(dirname(__DIR__).'/public_html');
