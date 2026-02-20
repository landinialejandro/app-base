<?php
// app/Http/Middleware/CheckOrganizationAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Si no hay usuario autenticado, continuar (para rutas públicas)
        if (!$user) {
            return $next($request);
        }

        // SUPERADMIN: pasa siempre
        if ($user->is_platform_admin) {
            return $next($request);
        }

        // Verificar 1: ¿Tiene organización?
        if (!$user->organization) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Error: Usuario sin organización.']);
        }

        // Verificar 2: ¿Organización activa?
        if (!$user->organization->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Organización inactiva. Contacta al administrador.']);
        }

        // Verificar 3: ¿Usuario aprobado?
        if (!$user->approved_at) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Cuenta pendiente de aprobación.']);
        }

        // TODO OK: continuar
        return $next($request);
    }
}