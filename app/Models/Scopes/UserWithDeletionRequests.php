<?php
// app/Models/Scopes/UserWithDeletionRequests.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserWithDeletionRequests implements Scope {
    public function apply(Builder $builder, Model $model): void {
        $builder->whereNotNull('deletion_requested_at')
            ->where('deletion_approved', false);
    }
}
