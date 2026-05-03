<?php

// FILE: app/Support/Catalogs/ModuleCatalog.php | V5

namespace App\Support\Catalogs;

use App\Support\Appointments\AppointmentSurfaceService;
use App\Support\Assets\AssetSurfaceService;
use App\Support\Attachments\AttachmentSurfaceService;
use App\Support\Documents\DocumentSurfaceService;
use App\Support\Inventory\InventorySurfaceService;
use App\Support\Orders\OrderSurfaceService;
use App\Support\Parties\PartySurfaceService;
use App\Support\Products\ProductSurfaceService;
use App\Support\Projects\ProjectActivityRecordSet;
use App\Support\Projects\ProjectSurfaceService;
use App\Support\Tasks\TaskSurfaceService;

class ModuleCatalog
{
    public const DASHBOARD = 'dashboard';

    public const PROJECTS = 'projects';

    public const TASKS = 'tasks';

    public const PARTIES = 'parties';

    public const PRODUCTS = 'products';

    public const INVENTORY = 'inventory';

    public const ASSETS = 'assets';

    public const ORDERS = 'orders';

    public const DOCUMENTS = 'documents';

    public const APPOINTMENTS = 'appointments';

    public const ATTACHMENTS = 'attachments';

    protected static array $definitions = [
        self::DASHBOARD => [
            'label' => 'Dashboard',
            'icon' => 'grid',
        ],

        self::PROJECTS => [
            'label' => 'Proyectos',
            'icon' => 'folder',
            'surface_service' => ProjectSurfaceService::class,
            'activity_record_set' => ProjectActivityRecordSet::class,
            'nav' => [
                'group' => 'management',
                'route' => 'projects.index',
                'active' => ['projects.*'],
                'order' => 10,
            ],
        ],
        self::TASKS => [
            'label' => 'Tareas',
            'icon' => 'list-check',
            'surface_service' => TaskSurfaceService::class,
            'nav' => [
                'group' => 'main',
                'route' => 'tasks.index',
                'active' => ['tasks.*'],
                'order' => 10,
            ],
        ],

        self::APPOINTMENTS => [
            'label' => 'Turnos',
            'icon' => 'calendar',
            'surface_service' => AppointmentSurfaceService::class,
            'nav' => [
                'group' => 'main',
                'route' => 'appointments.calendar',
                'active' => ['appointments.*'],
                'order' => 15,
            ],
        ],

        self::PARTIES => [
            'label' => 'Contactos',
            'icon' => 'user-group',
            'surface_service' => PartySurfaceService::class,
            'nav' => [
                'group' => 'main',
                'route' => 'parties.index',
                'active' => ['parties.*'],
                'order' => 20,
            ],
        ],

        self::PRODUCTS => [
            'label' => 'Productos',
            'icon' => 'box',
            'surface_service' => ProductSurfaceService::class,
            'nav' => [
                'group' => 'management',
                'route' => 'products.index',
                'active' => ['products.*'],
                'order' => 20,
            ],
        ],

        self::INVENTORY => [
            'label' => 'Inventario',
            'icon' => 'archive-box',
            'surface_service' => InventorySurfaceService::class,
            'nav' => [
                'group' => 'management',
                'route' => 'inventory.index',
                'active' => ['inventory.*'],
                'order' => 25,
            ],
        ],

        self::ASSETS => [
            'label' => 'Activos',
            'icon' => 'screen',
            'surface_service' => AssetSurfaceService::class,
            'nav' => [
                'group' => 'main',
                'route' => 'assets.index',
                'active' => ['assets.*'],
                'order' => 30,
            ],
        ],

        self::ORDERS => [
            'label' => 'Órdenes',
            'icon' => 'orders',
            'surface_service' => OrderSurfaceService::class,
            'nav' => [
                'group' => 'management',
                'route' => 'orders.index',
                'active' => ['orders.*', 'orders.items.*'],
                'order' => 30,
            ],
        ],

        self::DOCUMENTS => [
            'label' => 'Documentos',
            'icon' => 'file-text',
            'surface_service' => DocumentSurfaceService::class,
            'nav' => [
                'group' => 'management',
                'route' => 'documents.index',
                'active' => ['documents.*'],
                'order' => 40,
            ],
        ],

        self::ATTACHMENTS => [
            'label' => 'Adjuntos',
            'icon' => 'paperclip',
            'surface_service' => AttachmentSurfaceService::class,
        ],
    ];

    public static function all(): array
    {
        return array_keys(static::$definitions);
    }

    public static function labels(): array
    {
        return collect(static::$definitions)
            ->mapWithKeys(fn (array $definition, string $module) => [$module => $definition['label']])
            ->all();
    }

    public static function definition(string $module): ?array
    {
        return static::$definitions[$module] ?? null;
    }

    public static function label(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$definitions[$value]['label'] ?? $default;
    }

    public static function icon(?string $module, string $default = 'box'): string
    {
        if ($module === null) {
            return $default;
        }

        return static::$definitions[$module]['icon'] ?? $default;
    }

    public static function surfaceService(string $module): ?string
    {
        return static::$definitions[$module]['surface_service'] ?? null;
    }

    public static function navDefinitions(): array
    {
        return collect(static::$definitions)
            ->filter(fn (array $definition) => isset($definition['nav']))
            ->map(function (array $definition, string $module) {
                return [
                    'module' => $module,
                    'label' => $definition['label'],
                    'route' => $definition['nav']['route'],
                    'active' => $definition['nav']['active'],
                    'group' => $definition['nav']['group'],
                    'order' => $definition['nav']['order'] ?? 999,
                    'icon' => $definition['icon'] ?? 'box',
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    public static function navDefinitionsByGroup(string $group): array
    {
        return collect(static::navDefinitions())
            ->where('group', $group)
            ->values()
            ->all();
    }

    public static function activityRecordSetService(string $module): ?string
    {
        return static::$definitions[$module]['activity_record_set'] ?? null;
    }
}
