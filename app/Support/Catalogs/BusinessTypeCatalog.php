<?php

// FILE: app/Support/Catalogs/BusinessTypeCatalog.php | V1

namespace App\Support\Catalogs;

class BusinessTypeCatalog
{
    public const GENERIC = 'generic';

    public const WORKSHOP = 'workshop';

    public const DENTISTRY = 'dentistry';

    public const CAR_WASH = 'car_wash';

    protected static array $labels = [
        self::GENERIC => 'Genérico',
        self::WORKSHOP => 'Taller',
        self::DENTISTRY => 'Odontología',
        self::CAR_WASH => 'Lavadero',
    ];

    public static function all(): array
    {
        return array_keys(static::$labels);
    }

    public static function labels(): array
    {
        return static::$labels;
    }

    public static function label(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$labels[$value] ?? $default;
    }
}
