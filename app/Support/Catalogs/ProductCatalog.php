<?php

// FILE: app/Support/Catalogs/ProductCatalog.php

namespace App\Support\Catalogs;

class ProductCatalog extends BaseCatalog
{
    public const KIND_PRODUCT = 'product';

    public const KIND_INTANGIBLE = 'intangible';

    public const KIND_SERVICE = 'service';

    protected static array $kinds = [
        self::KIND_PRODUCT => 'Producto',
        self::KIND_INTANGIBLE => 'Intangible',
        self::KIND_SERVICE => 'Servicio',
    ];

    public static function kindLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$kinds[$value] ?? $default;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$statuses[$value] ?? $default;
    }

    public static function activeLabel(bool $value): ?string
    {
        return StatusVocabulary::activeLabel($value);
    }

    public static function activeBadgeClass(bool $value): string
    {
        return StatusVocabulary::activeBadgeClass($value);
    }

    public static function activePresentation(bool $value): array
    {
        return StatusVocabulary::activePresentation($value);
    }


    public static function activityTrackedFields(): array
    {
        return [
            'name',
            'sku',
            'price',
            'kind',
            'is_stockable',
            'unit_label',
            'is_active',
        ];
    }
}
