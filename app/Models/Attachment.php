<?php

// FILE: app/Models/Attachment.php | V7

namespace App\Models;

use App\Models\Concerns\ResolvesTenantRouteBinding;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use ResolvesTenantRouteBinding;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'attachable_type',
        'attachable_id',
        'uploaded_by_user_id',
        'disk',
        'directory',
        'stored_name',
        'original_name',
        'extension',
        'mime_type',
        'size_bytes',
        'description',
        'is_image',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_image' => 'boolean',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function scopeFor($query, string $type, $id)
    {
        return $query
            ->where('attachable_type', $type)
            ->where('attachable_id', $id);
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderBy('sort_order')
            ->latest('id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getFileNameAttribute(): string
    {
        return (string) ($this->original_name ?: $this->stored_name ?: '');
    }

    public function getFullPathAttribute(): string
    {
        return $this->directory
            ? $this->directory.'/'.$this->stored_name
            : $this->stored_name;
    }
}
