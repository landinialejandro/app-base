<?php

// FILE: app/Support/Catalogs/TaskCatalog.php

namespace App\Support\Catalogs;

class TaskCatalog extends BaseCatalog
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

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

    protected static array $priorities = [
        self::PRIORITY_LOW => 'Baja',
        self::PRIORITY_MEDIUM => 'Media',
        self::PRIORITY_HIGH => 'Alta',
        self::PRIORITY_URGENT => 'Urgente',
    ];

    protected static array $priorityBadges = [
        self::PRIORITY_LOW => 'status-badge--done',
        self::PRIORITY_MEDIUM => 'status-badge--in-progress',
        self::PRIORITY_HIGH => 'status-badge--pending',
        self::PRIORITY_URGENT => 'status-badge--cancelled',
    ];

    public static function priorityLabels(): array
    {
        return static::$priorities;
    }

    public static function priorityLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$priorities[$value] ?? $default;
    }

    public static function priorityBadgeClass(?string $value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        return static::$priorityBadges[$value] ?? $default;
    }
}
