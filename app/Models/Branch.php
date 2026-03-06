<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\TenantScoped;

class Branch extends Model
{
    use TenantScoped;
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'city',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}