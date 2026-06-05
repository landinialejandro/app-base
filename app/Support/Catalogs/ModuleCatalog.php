<?php

// FILE: app/Support/Catalogs/ModuleCatalog.php | V12

namespace App\Support\Catalogs;

use App\Support\Appointments\AppointmentSurfaceService;
use App\Support\Assets\AssetSurfaceService;
use App\Support\Attachments\AttachmentSurfaceService;
use App\Support\Documents\DocumentSurfaceService;
use App\Support\Inventory\InventorySurfaceService;
use App\Support\Orders\OrderSurfaceService;
use App\Support\Parties\PartyEmployeeActivityContext;
use App\Support\Parties\PartySurfaceService;
use App\Support\Products\ProductSurfaceService;
use App\Support\Projects\ProjectActivityRecordSet;
use App\Support\Projects\ProjectSurfaceService;
use App\Support\Tasks\TaskSurfaceService;

class ModuleCatalog
{
    public const DASHBOARD = 'dashboard';

    public const SERVICE_MAINTENANCE = 'service_maintenance';

    public const PRODUCTION = 'production';

    public const PROJECTS = 'projects';

    public const TASKS = 'tasks';

    public const PARTIES = 'parties';

    public const PRODUCTS = 'products';

    public const SHOPS = 'shops';

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
            'accent' => 'primary',
        ],

        self::SERVICE_MAINTENANCE => [
            'label' => 'Servicio y mantenimiento',
            'icon' => 'wrench',
            'accent' => 'neutral',
        ],

        self::PRODUCTION => [
            'label' => 'Producción',
            'icon' => 'factory',
            'accent' => 'primary',
        ],

        self::PROJECTS => [
            'label' => 'Proyectos',
            'icon' => 'folder',
            'accent' => 'purple',
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
            'accent' => 'warning',
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
            'accent' => 'primary',
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
            'accent' => 'info',
            'surface_service' => PartySurfaceService::class,
            'activity_context' => PartyEmployeeActivityContext::class,
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
            'accent' => 'success',
            'surface_service' => ProductSurfaceService::class,
            'nav' => [
                'group' => 'management',
                'route' => 'products.index',
                'active' => ['products.*'],
                'order' => 20,
            ],
        ],

        self::SHOPS => [
            'label' => 'Tiendas',
            'icon' => 'store',
            'accent' => 'success',
            'nav' => [
                'group' => 'management',
                'route' => 'shops.index',
                'active' => ['shops.*'],
                'order' => 23,
            ],
        ],

        self::INVENTORY => [
            'label' => 'Inventario',
            'icon' => 'archive-box',
            'accent' => 'success',
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
            'accent' => 'neutral',
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
            'accent' => 'primary',
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
            'accent' => 'purple',
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
            'accent' => 'neutral',
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

    public static function accent(?string $module, string $default = 'primary'): string
    {
        if ($module === null) {
            return $default;
        }

        return static::$definitions[$module]['accent'] ?? $default;
    }

    public static function surfaceService(string $module): ?string
    {
        return static::$definitions[$module]['surface_service'] ?? null;
    }

    public static function activityContextService(string $module): ?string
    {
        return static::$definitions[$module]['activity_context'] ?? null;
    }

    public static function activityRecordSetService(string $module): ?string
    {
        return static::$definitions[$module]['activity_record_set'] ?? null;
    }

    public static function navDefinition(string $module): ?array
    {
        $definition = static::$definitions[$module] ?? null;

        if (! is_array($definition) || ! isset($definition['nav']) || ! is_array($definition['nav'])) {
            return null;
        }

        return [
            'module' => $module,
            'label' => $definition['label'],
            'icon' => $definition['icon'] ?? 'box',
            ...$definition['nav'],
        ];
    }

    public static function hasNav(string $module): bool
    {
        return static::navDefinition($module) !== null;
    }

    public static function navRoute(string $module): ?string
    {
        $route = static::navDefinition($module)['route'] ?? null;

        return is_string($route) && trim($route) !== '' ? $route : null;
    }

    public static function navGroup(string $module): ?string
    {
        $group = static::navDefinition($module)['group'] ?? null;

        return is_string($group) && trim($group) !== '' ? $group : null;
    }

    public static function navActivePatterns(string $module): array
    {
        $patterns = static::navDefinition($module)['active'] ?? [];

        if (! is_array($patterns)) {
            return [];
        }

        return array_values(array_filter(
            $patterns,
            fn ($pattern) => is_string($pattern) && trim($pattern) !== ''
        ));
    }

    public static function navOrder(string $module): ?int
    {
        $order = static::navDefinition($module)['order'] ?? null;

        return is_numeric($order) ? (int) $order : null;
    }

    public static function navDefinitions(): array
    {
        return collect(static::all())
            ->map(fn (string $module) => static::navDefinition($module))
            ->filter()
            ->sortBy('order')
            ->values()
            ->all();
    }
}
