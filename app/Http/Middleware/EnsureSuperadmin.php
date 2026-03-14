<?php

// FILE: app/Http/Middleware/EnsureSuperadmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->is_superadmin) {
            abort(403, 'No autorizado.');
        }

        return $next($request);
    }
}
