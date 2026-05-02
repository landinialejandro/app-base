<?php

namespace App\Support\Catalogs;

class AssetCatalog extends BaseCatalog
{
    public const KIND_VEHICLE = 'vehicle';
    public const KIND_MACHINERY = 'machinery';
    public const KIND_EQUIPMENT = 'equipment';
    public const KIND_TOOL = 'tool';
    public const KIND_PROPERTY = 'property';
    public const KIND_DEVICE = 'device';
    public const KIND_OTHER = 'other';

    public const RELATIONSHIP_OWNED = 'owned';
    public const RELATIONSHIP_CUSTOMER = 'customer';
    public const RELATIONSHIP_RENTED = 'rented';
    public const RELATIONSHIP_BORROWED = 'borrowed';
    public const RELATIONSHIP_THIRD_PARTY = 'third_party';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    protected static array $kinds = [
        self::KIND_VEHICLE => 'Vehículo',
        self::KIND_MACHINERY => 'Maquinaria',
        self::KIND_EQUIPMENT => 'Equipo',
        self::KIND_TOOL => 'Herramienta',
        self::KIND_PROPERTY => 'Propiedad',
        self::KIND_DEVICE => 'Dispositivo',
        self::KIND_OTHER => 'Otro',
    ];

    protected static array $relationshipTypes = [
        self::RELATIONSHIP_OWNED => 'Propio',
        self::RELATIONSHIP_CUSTOMER => 'De cliente',
        self::RELATIONSHIP_RENTED => 'Alquilado',
        self::RELATIONSHIP_BORROWED => 'Prestado',
        self::RELATIONSHIP_THIRD_PARTY => 'De tercero',
    ];

    protected static array $statuses = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_INACTIVE => 'Inactivo',
        self::STATUS_ARCHIVED => 'Archivado',
    ];

    protected static array $badges = [
        self::STATUS_ACTIVE => 'status-badge--done',
        self::STATUS_INACTIVE => 'status-badge--pending',
        self::STATUS_ARCHIVED => 'status-badge--cancelled',
    ];

    public static function relationshipTypes(): array
    {
        return array_keys(static::$relationshipTypes);
    }

    public static function relationshipTypeLabels(): array
    {
        return static::$relationshipTypes;
    }

    public static function relationshipTypeLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$relationshipTypes[$value] ?? $default;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$statuses[$value] ?? $default;
    }

    public static function kindLabel(?string $value, ?string $default = '—'): ?string
{
    if ($value === null) {
        return $default;
    }

    return static::$kinds[$value] ?? $default;
}


    public static function activityTrackedFields(): array
    {
        return [
            'party_id',
            'kind',
            'relationship_type',
            'name',
            'internal_code',
            'status',
            'notes',
        ];
    }
}