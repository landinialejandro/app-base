<?php

// FILE: app/Models/Document.php | V6

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'party_id',
        'counterparty_name',
        'order_id',
        'asset_id',
        'group',
        'kind',
        'number',
        'sequence_prefix',
        'point_of_sale',
        'sequence_number',
        'status',
        'issued_at',
        'due_at',
        'currency_code',
        'subtotal',
        'tax_total',
        'total',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'sequence_number' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function items()
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable')->ordered();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function displayCounterpartyName(): string
    {
        return (string) ($this->counterparty_name ?: $this->party?->name ?: $this->order?->displayCounterpartyName() ?: '—');
    }
}