<?php

// FILE: app/Models/Attachment.php | V3

namespace App\Models;

use App\Support\Attachments\AttachmentCategory;
use App\Support\Attachments\AttachmentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;

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
        'checksum_sha256',
        'kind',
        'category',
        'is_image',
        'sort_order',
        'title',
        'description',
        'tags_json',
        'visibility',
        'meta_json',
        'extracted_text',
        'analysis_status',
        'analyzed_at',
        'analysis_version',
    ];

    protected $casts = [
        'is_image' => 'boolean',
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
        'tags_json' => 'array',
        'meta_json' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function getStoragePathAttribute(): string
    {
        $directory = trim((string) $this->directory, '/');
        $storedName = ltrim((string) $this->stored_name, '/');

        return $directory !== ''
            ? $directory.'/'.$storedName
            : $storedName;
    }

    public function getDisplayNameAttribute(): string
    {
        $title = trim((string) $this->title);

        return $title !== '' ? $title : (string) $this->original_name;
    }

    public function getKindLabelAttribute(): string
    {
        return AttachmentKind::label($this->kind);
    }

    public function getCategoryLabelAttribute(): string
    {
        return AttachmentCategory::label($this->category);
    }

    public function getExtensionLabelAttribute(): string
    {
        return strtoupper((string) ($this->extension ?? ''));
    }

    public function getSizeLabelAttribute(): string
    {
        $sizeBytes = (int) ($this->size_bytes ?? 0);

        if ($sizeBytes <= 0) {
            return '—';
        }

        if ($sizeBytes < 1024) {
            return $sizeBytes.' B';
        }

        if ($sizeBytes < 1024 * 1024) {
            return number_format($sizeBytes / 1024, 2, ',', '.').' KB';
        }

        return number_format($sizeBytes / (1024 * 1024), 2, ',', '.').' MB';
    }
}
