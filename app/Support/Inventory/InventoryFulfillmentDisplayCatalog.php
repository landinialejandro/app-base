<?php

// FILE: app/Support/Inventory/InventoryFulfillmentDisplayCatalog.php | V1

namespace App\Support\Inventory;

class InventoryFulfillmentDisplayCatalog
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static array $labels = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PENDING_APPROVAL => 'Pendiente de aprobación',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_PARTIALLY_FULFILLED => 'Parcialmente surtida',
        self::STATUS_FULFILLED => 'Surtida',
        self::STATUS_CLOSED => 'Cerrada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected static array $badges = [
        self::STATUS_DRAFT => 'status-badge--pending',
        self::STATUS_PENDING_APPROVAL => 'status-badge--warning',
        self::STATUS_APPROVED => 'status-badge--warning',
        self::STATUS_PARTIALLY_FULFILLED => 'status-badge--in-progress',
        self::STATUS_FULFILLED => 'status-badge--done',
        self::STATUS_CLOSED => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
    ];

    public static function label(?string $status, string $default = '—'): string
    {
        return $status !== null ? (static::$labels[$status] ?? $default) : $default;
    }

    public static function badgeClass(?string $status, string $default = 'status-badge--neutral'): string
    {
        return $status !== null ? (static::$badges[$status] ?? $default) : $default;
    }
}