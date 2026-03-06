<?php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use TenantScoped;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];
}