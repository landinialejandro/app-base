<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'doc_type',
        'prefix',
        'point_of_sale',
        'padding',
        'next_number',
    ];

    protected $casts = [
        'padding' => 'integer',
        'next_number' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}