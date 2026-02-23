<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectFilamentLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Si es una ruta de login de Filament y el usuario no está autenticado
        if (
            !auth()->check() && 
            ($request->is('*/login') || $request->is('super/login') || $request->is('admin/login') || $request->is('app/login'))
        ) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}