<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrganizationScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Superadmin no tiene restricciones
        if ($user->is_platform_admin) {
            return $next($request);
        }
        
        // Verificar que el usuario tenga organización
        if (!$user->organization) {
            auth()->logout();
            return redirect()->route('/app/login')
                ->withErrors(['email' => 'No tienes organización asignada.']);
        }
        
        // Verificar que la organización esté activa
        if (!$user->organization->is_active || $user->organization->isBlocked()) {
            auth()->logout();
            return redirect()->route('/app/login')
                ->withErrors(['email' => 'Organización no disponible. Contacta al administrador.']);
        }
        
        // Verificar que el usuario esté aprobado
        if (!$user->isApproved()) {
            auth()->logout();
            return redirect()->route('/app/login')
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación.']);
        }
        
        return $next($request);
    }
}
