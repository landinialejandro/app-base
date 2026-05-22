<?php

// FILE: app/Support/Inventory/InventoryFulfillmentDisplayCatalog.php | V1

namespace App\Support\Inventory;

use App\Support\Catalogs\StatusVocabulary;

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

    protected static array $statusIntentions = [
        self::STATUS_DRAFT => StatusVocabulary::DRAFT,
        self::STATUS_PENDING_APPROVAL => StatusVocabulary::PENDING,
        self::STATUS_APPROVED => StatusVocabulary::APPROVED,
        self::STATUS_PARTIALLY_FULFILLED => StatusVocabulary::PARTIAL,
        self::STATUS_FULFILLED => StatusVocabulary::COMPLETED,
        self::STATUS_CLOSED => StatusVocabulary::CLOSED,
        self::STATUS_CANCELLED => StatusVocabulary::CANCELLED,
    ];

    public static function label(?string $status, string $default = '—'): string
    {
        return $status !== null ? (static::$labels[$status] ?? $default) : $default;
    }

    public static function statusIntention(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return static::$statusIntentions[$status] ?? $status;
    }

    public static function badgeClass(?string $status, string $default = 'status-badge--neutral'): string
    {
        return StatusVocabulary::badgeClass(static::statusIntention($status), $default);
    }
}