<?php

// file: app/Http/Middleware/ResolveTenant.php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $tenant = null;
        $user = Auth::user();

        // 1) tenant desde sesión (flujo web normal)
        if (session()->has('tenant_id')) {
            $tenant = Tenant::find(session('tenant_id'));

            // Si hay usuario autenticado, validar pertenencia
            if ($tenant && $user) {
                $allowed = $user->tenants()
                    ->where('tenants.id', $tenant->id)
                    ->exists();

                if (!$allowed) {
                    session()->forget('tenant_id');
                    $tenant = null;
                }
            }
        }

        // 2) fallback: header X-Tenant (API/debug)
        if (!$tenant && $request->hasHeader('X-Tenant')) {
            $tenant = Tenant::where('slug', $request->header('X-Tenant'))->first();
        }

        // 3) si sigue sin tenant
        if (!$tenant) {
            if ($user) {
                return redirect()->route('tenants.select');
            }

            abort(404, 'Tenant not found');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}