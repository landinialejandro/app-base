<?php

// FILE: app/Models/Party.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// IMPORTANTE:
// usar exactamente los mismos namespaces de traits
// que ya utiliza el modelo Project en este proyecto.
use App\Models\Concerns\TenantScoped;
use App\Models\Concerns\ResolvesTenantRouteBinding;

class Party extends Model
{
    use HasFactory;
    use TenantScoped;
    use SoftDeletes;
    use ResolvesTenantRouteBinding;

    protected $fillable = [
        'tenant_id',
        'kind',
        'name',
        'display_name',
        'document_type',
        'document_number',
        'tax_id',
        'email',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}