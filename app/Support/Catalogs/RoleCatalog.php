<?php

// FILE: app/Support/Catalogs/RoleCatalog.php | V3

namespace App\Support\Catalogs;

class RoleCatalog
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const SALES = 'sales';

    public const OPERATOR = 'operator';

    public const ADMINISTRATOR = 'administrator';

    protected static array $labels = [
        self::OWNER => 'Propietario',
        self::ADMIN => 'Administrador',
        self::SALES => 'Comercial',
        self::OPERATOR => 'Operador',
        self::ADMINISTRATOR => 'Administrativo',
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
            self::ADMINISTRATOR,
        ];
    }

    public static function assignable(): array
    {
        return [
            self::ADMIN,
            self::SALES,
            self::OPERATOR,
            self::ADMINISTRATOR,
        ];
    }

    public static function exclusive(): array
    {
        return [
            self::OWNER,
            self::ADMIN,
        ];
    }

    public static function isAssignable(?string $role): bool
    {
        return in_array($role, static::assignable(), true);
    }

    public static function isExclusive(?string $role): bool
    {
        return in_array($role, static::exclusive(), true);
    }

    public static function defaultOperational(): string
    {
        return self::OPERATOR;
    }
}
