<?php

// FILE: app/Support/Attachments/AttachmentCategory.php | V1

namespace App\Support\Attachments;

class AttachmentCategory
{
    public const MANUAL = 'manual';

    public const EVIDENCE = 'evidence';

    public const SUPPORT = 'support';

    public const OTHER = 'other';

    public static function options(): array
    {
        return [
            static::MANUAL => 'Manuales',
            static::EVIDENCE => 'Evidencias',
            static::SUPPORT => 'Soporte',
            static::OTHER => 'Otros',
        ];
    }

    public static function values(): array
    {
        return array_keys(static::options());
    }

    public static function label(?string $value): string
    {
        return static::options()[$value] ?? 'Otros';
    }
}
