<?php

// FILE: app/Support/Catalogs/Concerns/HasLineItemStatuses.php | V1

namespace App\Support\Catalogs\Concerns;

use App\Support\Catalogs\StatusVocabulary;

trait HasLineItemStatuses
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected static array $statusIntentions = [
        self::STATUS_PENDING => StatusVocabulary::PENDING,
        self::STATUS_PARTIAL => StatusVocabulary::PARTIAL,
        self::STATUS_COMPLETED => StatusVocabulary::COMPLETED,
        self::STATUS_CANCELLED => StatusVocabulary::CANCELLED,
    ];

    public static function statusIntention(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return static::$statusIntentions[$value] ?? $value;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        return StatusVocabulary::label(static::statusIntention($value), $default);
    }

    public static function badgeClass(?string $value, string $default = 'status-badge--pending'): string
    {
        return StatusVocabulary::badgeClass(static::statusIntention($value), $default);
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