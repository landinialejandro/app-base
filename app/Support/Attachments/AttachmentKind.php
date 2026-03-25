<?php

// FILE: app/Support/Attachments/AttachmentKind.php | V1

namespace App\Support\Attachments;

class AttachmentKind
{
    public const PHOTO = 'photo';

    public const MANUAL = 'manual';

    public const EVIDENCE = 'evidence';

    public const SUPPORT = 'support';

    public const TEXT = 'text';

    public const OTHER = 'other';

    public static function options(): array
    {
        return [
            static::PHOTO => 'Foto',
            static::MANUAL => 'Manual',
            static::EVIDENCE => 'Evidencia',
            static::SUPPORT => 'Soporte',
            static::TEXT => 'Texto',
            static::OTHER => 'Otro',
        ];
    }

    public static function values(): array
    {
        return array_keys(static::options());
    }

    public static function label(?string $value): string
    {
        return static::options()[$value] ?? 'Otro';
    }

    public static function defaultCategory(?string $kind): string
    {
        return match ($kind) {
            static::MANUAL => AttachmentCategory::MANUAL,
            static::EVIDENCE => AttachmentCategory::EVIDENCE,
            static::SUPPORT => AttachmentCategory::SUPPORT,
            default => AttachmentCategory::OTHER,
        };
    }
}
