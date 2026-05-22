<?php

// FILE: app/Support/Catalogs/SelfServiceStoreCustomerCatalog.php | V1

namespace App\Support\Catalogs;

final class SelfServiceStoreCustomerCatalog
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CANCELLED = 'cancelled';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Activa',
            self::STATUS_BLOCKED => 'Bloqueada',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public static function statusIntention(?string $value): ?string
    {
        return match ($value) {
            self::STATUS_ACTIVE => StatusVocabulary::ACTIVE,
            self::STATUS_BLOCKED => StatusVocabulary::BLOCKED,
            self::STATUS_CANCELLED => StatusVocabulary::CANCELLED,
            default => $value,
        };
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::statusLabels()[$value]
            ?? StatusVocabulary::label(static::statusIntention($value), $default);
    }

    public static function badgeClass(?string $value, string $default = 'status-badge--neutral'): string
    {
        return StatusVocabulary::badgeClass(static::statusIntention($value), $default);
    }

    public static function operationLabel(bool $value): string
    {
        return $value ? 'Operación habilitada' : 'Operación pendiente';
    }

    public static function operationShortLabel(bool $value): string
    {
        return $value ? 'Habilitada' : 'Pendiente';
    }

    public static function operationBadgeClass(bool $value): string
    {
        return StatusVocabulary::enabledBadgeClass($value);
    }

    public static function operationPresentation(bool $value): array
    {
        return [
            'key' => StatusVocabulary::enabledKey($value),
            'label' => static::operationLabel($value),
            'shortLabel' => static::operationShortLabel($value),
            'badgeClass' => static::operationBadgeClass($value),
        ];
    }
}