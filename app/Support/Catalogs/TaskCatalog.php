<?php

// FILE: app/Support/Catalogs/TaskCatalog.php

namespace App\Support\Catalogs;

class TaskCatalog extends BaseCatalog
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected static array $statuses = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_IN_PROGRESS => 'En progreso',
        self::STATUS_DONE => 'Finalizada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected static array $badges = [
        self::STATUS_PENDING => 'status-badge--pending',
        self::STATUS_IN_PROGRESS => 'status-badge--in-progress',
        self::STATUS_DONE => 'status-badge--done',
        self::STATUS_CANCELLED => 'status-badge--cancelled',
    ];

    public static function badgeClass(?string $value): string
    {
        return static::$badges[$value] ?? '';
    }
}
