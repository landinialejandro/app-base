<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\OrganizationScope; 
use App\Http\Middleware\CheckOrganizationAccess;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Agregar OrganizationScope al grupo 'web'
        $middleware->appendToGroup('web', [
            OrganizationScope::class,
        ]);

        // También puedes registrar un alias si lo necesitas para rutas específicas
        $middleware->alias([
            'organization.scope' => OrganizationScope::class,
        ]);
    })

    ->withMiddleware(function (Middleware $middleware) {
        // Agregar al grupo web (se ejecuta en todas las rutas web)
        $middleware->web(append: [
            CheckOrganizationAccess::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
