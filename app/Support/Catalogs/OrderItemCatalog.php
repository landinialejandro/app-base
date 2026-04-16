<?php

// FILE: app/Support/Catalogs/OrderItemCatalog.php | V1

namespace App\Support\Catalogs;

class OrderItemCatalog extends BaseCatalog
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected static array $statuses = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_PARTIAL => 'Parcial',
        self::STATUS_COMPLETED => 'Completado',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    protected static array $badges = [
        self::STATUS_PENDING => 'status-badge--pending',
        self::STATUS_PARTIAL => 'status-badge--warning',
        self::STATUS_COMPLETED => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
    ];

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$statuses[$value] ?? $default;
    }

    public static function badgeClass(?string $value, string $default = 'status-badge--pending'): string
    {
        if ($value === null) {
            return $default;
        }

        return static::$badges[$value] ?? $default;
    }

    public static function isFinal(?string $value): bool
    {
        return in_array($value, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public static function isOperable(?string $value): bool
    {
        return in_array($value, [
            self::STATUS_PENDING,
            self::STATUS_PARTIAL,
        ], true);
    }
}
