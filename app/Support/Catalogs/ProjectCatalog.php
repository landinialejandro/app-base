<?php

// FILE: app/Support/Catalogs/ProjectCatalog.php

namespace App\Support\Catalogs;

class ProjectCatalog extends BaseCatalog
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected static array $statuses = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_CLOSED => 'Cerrado',
    ];

    protected static array $badges = [
        self::STATUS_ACTIVE => 'status-badge--done',
        self::STATUS_CLOSED => 'status-badge--cancelled',
    ];

    public static function statuses(): array
    {
        return array_keys(static::$statuses);
    }


    public static function activityTrackedFields(): array
    {
        return [
            'name',
            'description',
            'status',
        ];
    }
}
