<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (! empty($model->tenant_id)) {
                return;
            }

            if (! app()->bound('currentTenant')) {
                return;
            }

            $tenant = app('currentTenant');

            if (! $tenant) {
                return;
            }

            $model->tenant_id = $tenant->id;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}