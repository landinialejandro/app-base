<?php

// FILE: app/Support/Catalogs/OrderCatalog.php | V2

namespace App\Support\Catalogs;

class OrderCatalog extends BaseCatalog
{
    public const KIND_SALE = 'sale';

    public const KIND_PURCHASE = 'purchase';

    public const KIND_SERVICE = 'service';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected static array $kinds = [
        self::KIND_SALE => 'Venta',
        self::KIND_PURCHASE => 'Compra',
        self::KIND_SERVICE => 'Servicio',
    ];

    protected static array $statuses = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_CLOSED => 'Cerrada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected static array $badges = [
        self::STATUS_DRAFT => 'status-badge--pending',
        self::STATUS_APPROVED => 'status-badge--warning',
        self::STATUS_CLOSED => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
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

    public static function directionForKind(?string $kind, string $default = 'out'): string
    {
        return match ($kind) {
            self::KIND_PURCHASE => 'in',
            self::KIND_SALE,
            self::KIND_SERVICE => 'out',
            default => $default,
        };
    }

    public static function isOperableStatus(?string $status): bool
    {
        return $status === self::STATUS_APPROVED;
    }

    public static function isReadonlyStatus(?string $status): bool
    {
        return in_array($status, [
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public static function canTransition(?string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            self::STATUS_DRAFT => in_array($to, [
                self::STATUS_APPROVED,
                self::STATUS_CANCELLED,
            ], true),
            self::STATUS_APPROVED => in_array($to, [
                self::STATUS_CLOSED,
                self::STATUS_CANCELLED,
            ], true),
            self::STATUS_CLOSED => false,
            self::STATUS_CANCELLED => false,
            default => false,
        };
    }
}
