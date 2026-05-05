<?php

// FILE: app/Support/Catalogs/OrderCatalog.php | V5

namespace App\Support\Catalogs;

class OrderCatalog extends BaseCatalog
{
    public const GROUP_SALE = 'sale';
    public const GROUP_PURCHASE = 'purchase';
    public const GROUP_SERVICE = 'service';

    public const KIND_STANDARD = 'standard';

    public const KIND_SALE = self::GROUP_SALE;
    public const KIND_PURCHASE = self::GROUP_PURCHASE;
    public const KIND_SERVICE = self::GROUP_SERVICE;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static array $groups = [
        self::GROUP_SALE => 'Venta',
        self::GROUP_PURCHASE => 'Compra',
        self::GROUP_SERVICE => 'Servicio',
    ];

    protected static array $kinds = [
        self::KIND_STANDARD => 'Estándar',
    ];

    protected static array $statuses = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PENDING_APPROVAL => 'Pendiente de aprobación',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_CLOSED => 'Cerrada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected static array $badges = [
        self::STATUS_DRAFT => 'status-badge--pending',
        self::STATUS_PENDING_APPROVAL => 'status-badge--warning',
        self::STATUS_APPROVED => 'status-badge--warning',
        self::STATUS_CLOSED => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
    ];

    public static function groups(): array
    {
        return static::$groups;
    }

    public static function groupLabels(): array
    {
        return static::$groups;
    }

    public static function groupLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$groups[$value] ?? $default;
    }

    public static function kindsByGroup(?string $group): array
    {
        return match ($group) {
            self::GROUP_SALE,
            self::GROUP_PURCHASE,
            self::GROUP_SERVICE => static::$kinds,
            default => static::$kinds,
        };
    }

    public static function kindLabels(?string $group = null): array
    {
        if ($group === null) {
            return static::$kinds;
        }

        return static::kindsByGroup($group);
    }

    public static function kindLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        if (isset(static::$kinds[$value])) {
            return static::$kinds[$value];
        }

        if (isset(static::$groups[$value])) {
            return static::$groups[$value];
        }

        return $default;
    }

    public static function statuses(): array
    {
        return array_keys(static::$statuses);
    }

    public static function statusLabels(): array
    {
        return static::$statuses;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$statuses[$value] ?? $default;
    }

    public static function badgeClass(?string $value, ?string $default = 'status-badge--neutral'): string
    {
        if ($value === null) {
            return $default;
        }

        return static::$badges[$value] ?? $default;
    }

    public static function directionFor(?string $group, ?string $kind = null, string $default = 'out'): string
    {
        return match ($group) {
            self::GROUP_PURCHASE => 'in',
            self::GROUP_SALE,
            self::GROUP_SERVICE => 'out',
            default => $default,
        };
    }

    public static function directionForKind(?string $kind, string $default = 'out'): string
    {
        return static::directionFor($kind, null, $default);
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
                self::STATUS_PENDING_APPROVAL,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_PENDING_APPROVAL => in_array($to, [
                self::STATUS_APPROVED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_APPROVED => in_array($to, [
                self::STATUS_CLOSED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_CLOSED => in_array($to, [
                self::STATUS_APPROVED,
            ], true),

            self::STATUS_CANCELLED => false,

            default => false,
        };
    }

public static function activityTrackedFields(): array
{
    return [
        'party_id',
        'counterparty_name',
        'asset_id',
        'group',
        'kind',
        'status',
        'ordered_at',
        'notes',
    ];
}
}