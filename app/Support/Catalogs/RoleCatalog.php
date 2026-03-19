<?php

namespace App\Support\Catalogs;

class RoleCatalog
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const SALES = 'sales';

    public const OPERATOR = 'operator';

    protected static array $labels = [
        self::OWNER => 'Owner',
        self::ADMIN => 'Admin',
        self::SALES => 'Comercial',
        self::OPERATOR => 'Operador',
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

    public static function ownerLike(): array
    {
        return [
            self::OWNER,
            self::ADMIN,
        ];
    }

    public static function operational(): array
    {
        return [
            self::SALES,
            self::OPERATOR,
        ];
    }
}
