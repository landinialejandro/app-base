<?php

// FILE: app/Support/Catalogs/StatusVocabulary.php | V1

namespace App\Support\Catalogs;

final class StatusVocabulary
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const BLOCKED = 'blocked';
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const EXPIRED = 'expired';
    public const EXPIRING = 'expiring';
    public const ENABLED = 'enabled';
    public const DISABLED = 'disabled';
    public const DRAFT = 'draft';
    public const APPROVED = 'approved';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';
    public const PARTIAL = 'partial';
    public const COMPLETED = 'completed';
    public const PUBLISHED = 'published';
    public const REJECTED = 'rejected';
    public const SENT = 'sent';
    public const SCHEDULED = 'scheduled';
    public const IN_PROGRESS = 'in_progress';
    public const DONE = 'done';
    public const HIDDEN = 'hidden';
    public const ARCHIVED = 'archived';
    public const NEUTRAL = 'neutral';
    public const WARNING = 'warning';

    public static function labels(): array
    {
        return [
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::BLOCKED => 'Bloqueado',
            self::PENDING => 'Pendiente',
            self::CONFIRMED => 'Confirmado',
            self::EXPIRED => 'Vencido',
            self::EXPIRING => 'Por vencer',
            self::ENABLED => 'Habilitado',
            self::DISABLED => 'No habilitado',
            self::DRAFT => 'Borrador',
            self::APPROVED => 'Aprobado',
            self::CLOSED => 'Cerrado',
            self::CANCELLED => 'Cancelado',
            self::PARTIAL => 'Parcial',
            self::COMPLETED => 'Completado',
            self::PUBLISHED => 'Publicado',
            self::REJECTED => 'Rechazado',
            self::SENT => 'Enviado',
            self::SCHEDULED => 'Programado',
            self::IN_PROGRESS => 'En curso',
            self::DONE => 'Realizado',
            self::HIDDEN => 'Oculto',
            self::ARCHIVED => 'Archivado',
            self::NEUTRAL => 'Sin estado',
            self::WARNING => 'Requiere atención',
        ];
    }

    public static function label(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::labels()[$value] ?? $default;
    }

    public static function badgeClasses(): array
    {
        return [
            self::ACTIVE => 'status-badge--done',
            self::INACTIVE => 'status-badge--neutral',
            self::BLOCKED => 'status-badge--cancelled',
            self::PENDING => 'status-badge--pending',
            self::CONFIRMED => 'status-badge--done',
            self::EXPIRED => 'status-badge--expired',
            self::EXPIRING => 'status-badge--expiring',
            self::ENABLED => 'status-badge--done',
            self::DISABLED => 'status-badge--pending',
            self::DRAFT => 'status-badge--pending',
            self::APPROVED => 'status-badge--approved',
            self::CLOSED => 'status-badge--done',
            self::CANCELLED => 'status-badge--cancelled',
            self::PARTIAL => 'status-badge--in-progress',
            self::COMPLETED => 'status-badge--done',
            self::PUBLISHED => 'status-badge--done',
            self::REJECTED => 'status-badge--rejected',
            self::SENT => 'status-badge--sent',
            self::SCHEDULED => 'status-badge--pending',
            self::IN_PROGRESS => 'status-badge--in-progress',
            self::DONE => 'status-badge--done',
            self::HIDDEN => 'status-badge--neutral',
            self::ARCHIVED => 'status-badge--neutral',
            self::NEUTRAL => 'status-badge--neutral',
            self::WARNING => 'status-badge--warning',
        ];
    }

    public static function badgeClass(?string $value, string $default = 'status-badge--neutral'): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::badgeClasses()[$value] ?? $default;
    }

    public static function presentation(
        ?string $value,
        ?string $defaultLabel = '—',
        string $defaultClass = 'status-badge--neutral'
    ): array {
        return [
            'key' => $value,
            'label' => static::label($value, $defaultLabel),
            'badgeClass' => static::badgeClass($value, $defaultClass),
        ];
    }

    public static function activeKey(bool $value): string
    {
        return $value ? self::ACTIVE : self::INACTIVE;
    }

    public static function activeLabel(bool $value): ?string
    {
        return static::label(static::activeKey($value));
    }

    public static function activeBadgeClass(bool $value): string
    {
        return static::badgeClass(static::activeKey($value));
    }

    public static function activePresentation(bool $value): array
    {
        return static::presentation(static::activeKey($value));
    }

    public static function enabledKey(bool $value): string
    {
        return $value ? self::ENABLED : self::PENDING;
    }

    public static function enabledLabel(bool $value): ?string
    {
        return $value ? static::label(self::ENABLED) : static::label(self::PENDING);
    }

    public static function enabledBadgeClass(bool $value): string
    {
        return static::badgeClass(static::enabledKey($value));
    }

    public static function enabledPresentation(bool $value): array
    {
        return static::presentation(static::enabledKey($value));
    }
    public static function has(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return array_key_exists($value, static::labels());
    }


    public static function expirationPresentation(?\Illuminate\Support\Carbon $expiresAt, int $expiringHours = 48): array
    {
        $isExpired = $expiresAt !== null && $expiresAt->isPast();
        $isExpiringSoon = $expiresAt !== null
            && ! $isExpired
            && now()->diffInHours($expiresAt, false) <= $expiringHours;
    
        $key = match (true) {
            $isExpired => self::EXPIRED,
            $isExpiringSoon => self::EXPIRING,
            default => self::SENT,
        };
    
        return [
            'key' => $key,
            'label' => match ($key) {
                self::EXPIRED => 'Vencida',
                self::EXPIRING => 'Próxima a vencer',
                self::SENT => 'Disponible',
                default => static::label($key),
            },
            'badgeClass' => 'status-badge ' . static::badgeClass($key),
            'isExpired' => $isExpired,
            'isExpiringSoon' => $isExpiringSoon,
        ];
    }
}