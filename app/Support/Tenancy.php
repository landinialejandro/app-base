<?php

namespace App\Support;

use App\Models\Tenant;

class Tenancy
{
    public static function set(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        app()->instance('tenant', $tenant);
        return $tenant;
    }
}