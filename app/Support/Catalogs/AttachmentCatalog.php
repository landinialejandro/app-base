<?php

// FILE: app/Support/Catalogs/AttachmentCatalog.php | V1

namespace App\Support\Catalogs;

class AttachmentCatalog extends BaseCatalog
{
    public const KIND_PHOTO = 'photo';

    public const KIND_MANUAL = 'manual';

    public const KIND_EVIDENCE = 'evidence';

    public const KIND_SUPPORT = 'support';

    public const KIND_TEXT = 'text';

    public const KIND_OTHER = 'other';

    protected static array $kinds = [
        self::KIND_PHOTO => 'Fotos',
        self::KIND_MANUAL => 'Manuales',
        self::KIND_EVIDENCE => 'Evidencias',
        self::KIND_SUPPORT => 'Soporte',
        self::KIND_TEXT => 'Textos',
        self::KIND_OTHER => 'Otros',
    ];

    public static function kindLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::$kinds[$value] ?? $default;
    }
}
