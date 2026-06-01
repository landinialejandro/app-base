<?php

// FILE: app/Support/Catalogs/SelfServiceTokenConsumptionAttemptCatalog.php | V1

namespace App\Support\Catalogs;

use App\Models\SelfServiceTokenConsumptionAttempt;

final class SelfServiceTokenConsumptionAttemptCatalog
{
    public static function statusLabels(): array
    {
        return [
            SelfServiceTokenConsumptionAttempt::STATUS_PENDING => 'Pendiente',
            SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED => 'Confirmado',
            SelfServiceTokenConsumptionAttempt::STATUS_FAILED => 'Rechazado',
            SelfServiceTokenConsumptionAttempt::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public static function statusIntention(?string $value): ?string
    {
        return match ($value) {
            SelfServiceTokenConsumptionAttempt::STATUS_PENDING => StatusVocabulary::PENDING,
            SelfServiceTokenConsumptionAttempt::STATUS_CONFIRMED => StatusVocabulary::CONFIRMED,
            SelfServiceTokenConsumptionAttempt::STATUS_FAILED => StatusVocabulary::REJECTED,
            SelfServiceTokenConsumptionAttempt::STATUS_CANCELLED => StatusVocabulary::CANCELLED,
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

    public static function statusPresentation(?string $value): array
    {
        return [
            'key' => $value,
            'label' => static::statusLabel($value),
            'badgeClass' => static::badgeClass($value),
        ];
    }
}
