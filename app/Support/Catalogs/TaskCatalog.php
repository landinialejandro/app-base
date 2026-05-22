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

    protected static array $statusLabels = [
        self::STATUS_DONE => 'Finalizada',
    ];

    protected static array $statusIntentions = [
        self::STATUS_PENDING => StatusVocabulary::PENDING,
        self::STATUS_IN_PROGRESS => StatusVocabulary::IN_PROGRESS,
        self::STATUS_DONE => StatusVocabulary::DONE,
        self::STATUS_CANCELLED => StatusVocabulary::CANCELLED,
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

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => StatusVocabulary::label(self::STATUS_PENDING),
            self::STATUS_IN_PROGRESS => StatusVocabulary::label(self::STATUS_IN_PROGRESS),
            self::STATUS_DONE => static::$statusLabels[self::STATUS_DONE],
            self::STATUS_CANCELLED => StatusVocabulary::label(self::STATUS_CANCELLED),
        ];
    }

    public static function statusIntention(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return static::$statusIntentions[$value] ?? $value;
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$statusLabels[$value]
            ?? StatusVocabulary::label(static::statusIntention($value), $default);
    }

    public static function label(?string $value): string
    {
        return static::statusLabel($value, $value) ?? (string) $value;
    }

    public static function badgeClass(?string $value): string
    {
        return StatusVocabulary::badgeClass(static::statusIntention($value), '');
    }


    public static function activityTrackedFields(): array
    {
        return [
            'project_id',
            'party_id',
            'assigned_user_id',
            'name',
            'description',
            'status',
            'priority',
            'due_date',
        ];
    }
}
