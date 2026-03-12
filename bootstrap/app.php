<?php

// FILE: bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, $request): Response {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesión ha expirado.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('error', 'Tu sesión expiró. Inicia sesión nuevamente.');
        });
    })
    ->create();
