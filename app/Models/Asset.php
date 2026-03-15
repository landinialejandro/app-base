<?php

// FILE: app/Models/Asset.php

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory;
    use TenantScoped;
    use ResolvesTenantRouteBinding;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'kind',
        'relationship_type',
        'name',
        'internal_code',
        'status',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}