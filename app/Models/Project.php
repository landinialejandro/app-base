<?php

// FILE: app/Models/Project.php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
