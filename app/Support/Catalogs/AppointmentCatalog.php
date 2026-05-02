<?php

namespace App\Support\Catalogs;

use App\Support\Tenants\TenantBusinessContext;

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

    public static function contactLabel(): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => 'Cliente',
            BusinessTypeCatalog::DENTISTRY => 'Paciente',
            BusinessTypeCatalog::CAR_WASH => 'Cliente',
            default => 'Contacto',
        };
    }

    public static function assetLabel(): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => 'Vehículo',
            BusinessTypeCatalog::DENTISTRY => 'Ficha o tratamiento',
            BusinessTypeCatalog::CAR_WASH => 'Vehículo',
            default => 'Activo',
        };
    }

    public static function assignedUserLabel(): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => 'Asignado a',
            BusinessTypeCatalog::DENTISTRY => 'Profesional',
            BusinessTypeCatalog::CAR_WASH => 'Operario',
            default => 'Asignado a',
        };
    }

    public static function orderLabel(): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => 'Orden',
            BusinessTypeCatalog::DENTISTRY => 'Prestación',
            BusinessTypeCatalog::CAR_WASH => 'Orden',
            default => 'Orden',
        };
    }

    public static function workPlaceLabel(): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::DENTISTRY => 'Lugar de atención',
            default => 'Lugar de trabajo',
        };
    }

    public static function referenceLabelForKind(?string $kind): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => match ($kind) {
                self::KIND_SERVICE => 'Ubicación en taller',
                self::KIND_VISIT => 'Dirección',
                self::KIND_BLOCK => 'Referencia',
                default => 'Referencia',
            },

            BusinessTypeCatalog::DENTISTRY => match ($kind) {
                self::KIND_SERVICE => 'Consultorio',
                self::KIND_VISIT => 'Dirección',
                self::KIND_BLOCK => 'Referencia',
                default => 'Referencia',
            },

            BusinessTypeCatalog::CAR_WASH => match ($kind) {
                self::KIND_SERVICE => 'Box o sector',
                self::KIND_VISIT => 'Dirección',
                self::KIND_BLOCK => 'Referencia',
                default => 'Referencia',
            },

            default => match ($kind) {
                self::KIND_SERVICE => 'Referencia del lugar',
                self::KIND_VISIT => 'Dirección',
                self::KIND_BLOCK => 'Referencia',
                default => 'Referencia',
            },
        };
    }

    public static function rowTitleFor(?string $kind, ?string $workMode): string
    {
        return match (TenantBusinessContext::type()) {
            BusinessTypeCatalog::WORKSHOP => match (true) {
                $kind === self::KIND_BLOCK => 'Bloqueo de agenda',
                $kind === self::KIND_VISIT => 'Turno de visita',
                $workMode === self::WORK_MODE_FIELD_ASSISTANCE => 'Turno de asistencia externa',
                default => 'Turno de taller',
            },

            BusinessTypeCatalog::DENTISTRY => match (true) {
                $kind === self::KIND_BLOCK => 'Bloqueo de agenda',
                $kind === self::KIND_VISIT => 'Visita profesional',
                default => 'Turno de atención',
            },

            BusinessTypeCatalog::CAR_WASH => match (true) {
                $kind === self::KIND_BLOCK => 'Bloqueo de agenda',
                $kind === self::KIND_VISIT => 'Visita de servicio',
                default => 'Turno de lavado',
            },

            default => match (true) {
                $kind === self::KIND_BLOCK => 'Bloqueo de agenda',
                $kind === self::KIND_VISIT => 'Turno de visita',
                default => 'Turno',
            },
        };
    }


    public static function activityTrackedFields(): array
    {
        return [
            'party_id',
            'order_id',
            'asset_id',
            'assigned_user_id',
            'kind',
            'status',
            'work_mode',
            'title',
            'notes',
            'workstation_name',
            'scheduled_date',
            'starts_at',
            'ends_at',
            'is_all_day',
        ];
    }
}
