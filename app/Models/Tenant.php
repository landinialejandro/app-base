<?php

//FILE: app/Models/Tenant.php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}