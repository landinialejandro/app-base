<?php

// FILE: app/Support/Tenants/OperationalActivityCatalog.php | V5

namespace App\Support\Tenants;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\InventoryOperation;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\AssetCatalog;
use App\Support\Catalogs\AttachmentCatalog;
use App\Support\Catalogs\DocumentCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\PartyCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Catalogs\ProjectCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Inventory\InventoryOperationCatalog;

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
            Asset::class => ModuleCatalog::ASSETS,
            Attachment::class => ModuleCatalog::ATTACHMENTS,
            Document::class => ModuleCatalog::DOCUMENTS,
            InventoryOperation::class => ModuleCatalog::INVENTORY,
            Order::class => ModuleCatalog::ORDERS,
            Party::class => ModuleCatalog::PARTIES,
            Product::class => ModuleCatalog::PRODUCTS,
            Project::class => ModuleCatalog::PROJECTS,
            Task::class => ModuleCatalog::TASKS,
            default => null,
        };
    }

    public static function catalogForModule(string $module): ?string
    {
        return match ($module) {
            ModuleCatalog::APPOINTMENTS => AppointmentCatalog::class,
            ModuleCatalog::ASSETS => AssetCatalog::class,
            ModuleCatalog::ATTACHMENTS => AttachmentCatalog::class,
            ModuleCatalog::DOCUMENTS => DocumentCatalog::class,
            ModuleCatalog::INVENTORY => InventoryOperationCatalog::class,
            ModuleCatalog::ORDERS => OrderCatalog::class,
            ModuleCatalog::PARTIES => PartyCatalog::class,
            ModuleCatalog::PRODUCTS => ProductCatalog::class,
            ModuleCatalog::PROJECTS => ProjectCatalog::class,
            ModuleCatalog::TASKS => TaskCatalog::class,
            default => null,
        };
    }
}