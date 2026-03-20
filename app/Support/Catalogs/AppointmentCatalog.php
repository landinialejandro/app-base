<?php

namespace App\Support\Catalogs;

class AppointmentCatalog extends BaseCatalog
{
    public const KIND_SERVICE = 'service';

    public const KIND_VISIT = 'visit';

    public const KIND_BLOCK = 'block';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const WORK_MODE_IN_SHOP = 'in_shop';

    public const WORK_MODE_ON_SITE = 'on_site';

    public const WORK_MODE_FIELD_ASSISTANCE = 'field_assistance';

    protected static array $statuses = [
        self::STATUS_SCHEDULED => 'Programado',
        self::STATUS_COMPLETED => 'Completado',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    protected static array $badges = [
        self::STATUS_SCHEDULED => 'status-badge--pending',
        self::STATUS_COMPLETED => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
    ];

    protected static array $kinds = [
        self::KIND_SERVICE => 'Turno normal',
        self::KIND_VISIT => 'Visita',
        self::KIND_BLOCK => 'Bloqueo',
    ];

    protected static array $workModes = [
        self::WORK_MODE_IN_SHOP => 'Taller',
        self::WORK_MODE_ON_SITE => 'A domicilio',
        self::WORK_MODE_FIELD_ASSISTANCE => 'Asistencia externa',
    ];

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

    public static function workModeLabels(): array
    {
        return static::$workModes;
    }

    public static function workModeLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$workModes[$value] ?? $default;
    }

    public static function blockingStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
        ];
    }

    public static function suggestedWorkModeForKind(?string $kind): ?string
    {
        return match ($kind) {
            self::KIND_SERVICE => self::WORK_MODE_IN_SHOP,
            self::KIND_VISIT => self::WORK_MODE_ON_SITE,
            default => null,
        };
    }

    public static function referenceLabelForKind(?string $kind): string
    {
        return match ($kind) {
            self::KIND_SERVICE => 'Ubicación en taller',
            self::KIND_VISIT => 'Dirección',
            self::KIND_BLOCK => 'Referencia',
            default => 'Referencia',
        };
    }
}
