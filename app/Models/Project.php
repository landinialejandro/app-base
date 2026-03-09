<?php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;
    use TenantScoped;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];
}