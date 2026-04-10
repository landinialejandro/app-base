<?php

// FILE: app/Support/Catalogs/ModuleCatalog.php | V2

namespace App\Support\Catalogs;

class ModuleCatalog
{
    public const DASHBOARD = 'dashboard';

    public const PROJECTS = 'projects';

    public const TASKS = 'tasks';

    public const PARTIES = 'parties';

    public const PRODUCTS = 'products';

    public const ASSETS = 'assets';

    public const ORDERS = 'orders';

    public const DOCUMENTS = 'documents';

    public const APPOINTMENTS = 'appointments';

    public const ATTACHMENTS = 'attachments';

    protected static array $definitions = [
        self::DASHBOARD => [
            'label' => 'Dashboard',
        ],

        self::PROJECTS => [
            'label' => 'Proyectos',
            'nav' => [
                'group' => 'management',
                'route' => 'projects.index',
                'active' => ['projects.*'],
                'order' => 10,
            ],
        ],

        self::TASKS => [
            'label' => 'Tareas',
            'nav' => [
                'group' => 'main',
                'route' => 'tasks.index',
                'active' => ['tasks.*'],
                'order' => 10,
            ],
        ],

        self::APPOINTMENTS => [
            'label' => 'Turnos',
            'nav' => [
                'group' => 'main',
                'route' => 'appointments.index',
                'active' => ['appointments.*'],
                'order' => 15,
            ],
        ],

        self::PARTIES => [
            'label' => 'Contactos',
            'nav' => [
                'group' => 'main',
                'route' => 'parties.index',
                'active' => ['parties.*'],
                'order' => 20,
            ],
        ],

        self::PRODUCTS => [
            'label' => 'Productos',
            'nav' => [
                'group' => 'management',
                'route' => 'products.index',
                'active' => ['products.*'],
                'order' => 20,
            ],
        ],

        self::ASSETS => [
            'label' => 'Activos',
            'nav' => [
                'group' => 'main',
                'route' => 'assets.index',
                'active' => ['assets.*'],
                'order' => 30,
            ],
        ],

        self::ORDERS => [
            'label' => 'Órdenes',
            'nav' => [
                'group' => 'management',
                'route' => 'orders.index',
                'active' => ['orders.*', 'orders.items.*'],
                'order' => 30,
            ],
        ],

        self::DOCUMENTS => [
            'label' => 'Documentos',
            'nav' => [
                'group' => 'management',
                'route' => 'documents.index',
                'active' => ['documents.*'],
                'order' => 40,
            ],
        ],

        self::ATTACHMENTS => [
            'label' => 'Adjuntos',
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
}
