<?php

use App\Http\Middleware\RolRequerido;
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
        $middleware->alias(['rol' => RolRequerido::class]);

        // Meta no envía token CSRF: el webhook se protege con la firma X-Hub-Signature-256.
        $middleware->validateCsrfTokens(except: ['webhook/whatsapp']);

        // Quien no inició sesión va al formulario de acceso.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
