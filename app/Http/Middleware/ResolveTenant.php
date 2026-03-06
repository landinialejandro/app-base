<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $tenant = null;

        // 1️⃣ tenant desde sesión (flujo web normal)
        if (session()->has('tenant_id')) {
            $tenant = \App\Models\Tenant::find(session('tenant_id'));
        }

        // 2️⃣ fallback: header X-Tenant (API/debug)
        if (!$tenant && $request->hasHeader('X-Tenant')) {
            $tenant = \App\Models\Tenant::where('slug', $request->header('X-Tenant'))->first();
        }

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}