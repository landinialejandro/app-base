<?php

// FILE: app/Http/Middleware/ResolveSelfServiceExternalCustomer.php | V3

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceExternalSession;
use Closure;
use Illuminate\Http\Request;

class ResolveSelfServiceExternalCustomer
{
    public function __construct(
        protected SelfServiceExternalSession $externalSession
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->resolveRouteTenant($request);

        if (! $tenant) {
            return $next($request);
        }

        if (! $this->externalSession->isSelectedForTenant($tenant)) {
            return $next($request);
        }

        $payload = $this->externalSession->payload();

        if ($payload) {
            $request->attributes->set('self_service_external_customer', $payload);
        }

        return $next($request);
    }

    protected function resolveRouteTenant(Request $request): ?Tenant
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        if (is_string($tenant) && $tenant !== '') {
            return Tenant::query()
                ->where('slug', $tenant)
                ->first();
        }

        return null;
    }
}