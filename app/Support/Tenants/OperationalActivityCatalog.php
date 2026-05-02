<?php

// FILE: app/Support/Tenants/OperationalActivityCatalog.php | V1

namespace App\Support\Tenants;

use App\Models\Appointment;
use App\Models\Document;
use App\Models\Order;
use App\Models\Task;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\TaskCatalog;

class OperationalActivityCatalog
{
    public const TYPE_CREATED = 'created';
    public const TYPE_UPDATED = 'updated';
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_REASSIGNED = 'reassigned';
    public const TYPE_UNASSIGNED = 'unassigned';
    public const TYPE_STATUS_CHANGED = 'status_changed';

    public static function types(): array
    {
        return [
            self::TYPE_CREATED,
            self::TYPE_UPDATED,
            self::TYPE_ASSIGNED,
            self::TYPE_REASSIGNED,
            self::TYPE_UNASSIGNED,
            self::TYPE_STATUS_CHANGED,
        ];
    }

    public static function ignoredFields(): array
    {
        return [
            'id',
            'tenant_id',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
        ];
    }

    public static function moduleForRecordClass(string $recordClass): ?string
    {
        return match ($recordClass) {
            Appointment::class => ModuleCatalog::APPOINTMENTS,
            Order::class => ModuleCatalog::ORDERS,
            Document::class => ModuleCatalog::DOCUMENTS,
            Task::class => ModuleCatalog::TASKS,
            default => null,
        };
    }

    public static function catalogForModule(string $module): ?string
    {
        return match ($module) {
            ModuleCatalog::APPOINTMENTS => AppointmentCatalog::class,
            ModuleCatalog::ORDERS => OrderCatalog::class,
            ModuleCatalog::DOCUMENTS => DocumentCatalog::class,
            ModuleCatalog::TASKS => TaskCatalog::class,
            default => null,
        };
    }
}