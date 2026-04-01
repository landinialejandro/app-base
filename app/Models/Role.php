<?php

// FILE: app/Models/Role.php | V3

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withPivot(['scope', 'execution_mode', 'constraints'])
            ->withTimestamps();
    }
}
