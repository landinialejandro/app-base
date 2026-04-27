<?php

// FILE: app/Support/Catalogs/DocumentCatalog.php | V3

namespace App\Support\Catalogs;

class DocumentCatalog extends BaseCatalog
{
    public const GROUP_SALE = 'sale';

    public const GROUP_PURCHASE = 'purchase';

    public const GROUP_SERVICE = 'service';

    public const KIND_QUOTE = 'quote';

    public const KIND_INVOICE = 'invoice';

    public const KIND_DELIVERY_NOTE = 'delivery_note';

    public const KIND_WORK_ORDER = 'work_order';

    public const KIND_RECEIPT = 'receipt';

    public const KIND_CREDIT_NOTE = 'credit_note';

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
        self::KIND_QUOTE => 'Presupuesto',
        self::KIND_INVOICE => 'Factura',
        self::KIND_DELIVERY_NOTE => 'Remito',
        self::KIND_WORK_ORDER => 'Orden de trabajo',
        self::KIND_RECEIPT => 'Recibo',
        self::KIND_CREDIT_NOTE => 'Nota de crédito',
    ];

    protected static array $statuses = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PENDING_APPROVAL => 'Pendiente de aprobación',
        self::STATUS_APPROVED => 'Aprobado',
        self::STATUS_CLOSED => 'Cerrado',
        self::STATUS_CANCELLED => 'Cancelado',
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
        return array_keys(static::$groups);
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

    public static function kinds(): array
    {
        return array_keys(static::$kinds);
    }

    public static function kindLabels(): array
    {
        return static::$kinds;
    }

    public static function kindLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$kinds[$value] ?? $default;
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

    public static function isValidGroup(?string $group): bool
    {
        return $group !== null && in_array($group, static::groups(), true);
    }

    public static function isValidKind(?string $kind): bool
    {
        return $kind !== null && in_array($kind, static::kinds(), true);
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && in_array($status, static::statuses(), true);
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

    public static function stockAffectingKinds(): array
    {
        return [
            self::KIND_DELIVERY_NOTE,
            self::KIND_CREDIT_NOTE,
            self::KIND_WORK_ORDER,
        ];
    }

    public static function affectsStock(?string $group, ?string $kind): bool
    {
        if (! static::isValidGroup($group) || ! static::isValidKind($kind)) {
            return false;
        }

        return in_array($kind, static::stockAffectingKinds(), true);
    }

    public static function stockDirection(?string $group, ?string $kind): ?string
    {
        if (! static::affectsStock($group, $kind)) {
            return null;
        }

        if ($kind === self::KIND_CREDIT_NOTE) {
            return match ($group) {
                self::GROUP_SALE => 'in',
                self::GROUP_PURCHASE => 'out',
                default => null,
            };
        }

        if ($kind === self::KIND_DELIVERY_NOTE) {
            return match ($group) {
                self::GROUP_SALE, self::GROUP_SERVICE => 'out',
                self::GROUP_PURCHASE => 'in',
                default => null,
            };
        }

        if ($kind === self::KIND_WORK_ORDER) {
            return $group === self::GROUP_SERVICE ? 'out' : null;
        }

        return null;
    }

    public static function isFiscalKind(?string $kind): bool
    {
        return in_array($kind, [
            self::KIND_INVOICE,
            self::KIND_RECEIPT,
            self::KIND_CREDIT_NOTE,
        ], true);
    }

    public static function isExecutionKind(?string $kind): bool
    {
        return in_array($kind, [
            self::KIND_DELIVERY_NOTE,
            self::KIND_WORK_ORDER,
        ], true);
    }

    public static function isCommercialKind(?string $kind): bool
    {
        return in_array($kind, [
            self::KIND_QUOTE,
            self::KIND_INVOICE,
            self::KIND_DELIVERY_NOTE,
            self::KIND_WORK_ORDER,
            self::KIND_RECEIPT,
            self::KIND_CREDIT_NOTE,
        ], true);
    }

    public static function kindsForGroup(?string $group): array
    {
        return match ($group) {
            self::GROUP_SALE => [
                self::KIND_QUOTE,
                self::KIND_DELIVERY_NOTE,
                self::KIND_INVOICE,
                self::KIND_RECEIPT,
                self::KIND_CREDIT_NOTE,
            ],
            self::GROUP_PURCHASE => [
                self::KIND_DELIVERY_NOTE,
                self::KIND_INVOICE,
                self::KIND_RECEIPT,
                self::KIND_CREDIT_NOTE,
            ],
            self::GROUP_SERVICE => [
                self::KIND_QUOTE,
                self::KIND_WORK_ORDER,
                self::KIND_INVOICE,
                self::KIND_RECEIPT,
                self::KIND_CREDIT_NOTE,
            ],
            default => [],
        };
    }

    public static function kindLabelsForGroup(?string $group): array
    {
        return collect(static::kindLabels())
            ->only(static::kindsForGroup($group))
            ->all();
    }

    public static function isValidKindForGroup(?string $group, ?string $kind): bool
    {
        return in_array($kind, static::kindsForGroup($group), true);
    }
}