<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('currentTenant')) {
            return;
        }

        $tenant = app('currentTenant');

        if (! $tenant) {
            return;
        }

        $builder->where(
            $model->getTable() . '.tenant_id',
            $tenant->id
        );
    }
}