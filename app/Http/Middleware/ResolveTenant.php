<?php

// file: app/Http/Middleware/ResolveTenant.php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Auth;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $tenant = null;
        $user = Auth::user();

        if (session()->has('tenant_id')) {
            $tenant = Tenant::find(session('tenant_id'));

            if ($tenant && $user) {
                $allowed = $user->memberships()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->exists();

                if (! $allowed) {
                    session()->forget('tenant_id');
                    $tenant = null;
                }
            }
        }

        if (! $tenant && $request->hasHeader('X-Tenant')) {
            $tenant = Tenant::where('slug', $request->header('X-Tenant'))->first();

            if ($tenant && $user) {
                $allowed = $user->memberships()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->exists();

                if (! $allowed) {
                    $tenant = null;
                }
            }
        }

        if (! $tenant) {
            if ($user) {
                return redirect()->route('tenants.select');
            }

            abort(404, 'Tenant not found');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
