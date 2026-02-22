<?php
// app/Http/Middleware/SuperAdminOnly.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly {
    public function handle(Request $request, Closure $next): Response {
        if (!auth()->check() || !auth()->user()->is_platform_admin) {
            abort(403, 'Acceso restringido a super administradores.');
        }

        return $next($request);
    }
}
