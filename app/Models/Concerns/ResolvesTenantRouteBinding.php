<?php

// FILE: app/Models/Concerns/ResolvesTenantRouteBinding.php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

trait ResolvesTenantRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        $tenantId = session('tenant_id');

        if (! $tenantId) {
            abort(404);
        }

        $query = static::withoutGlobalScopes()
            ->where($field, $value)
            ->where('tenant_id', $tenantId);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            $query->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $query->firstOrFail();
    }
}