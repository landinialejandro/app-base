<?php

// FILE: app/Support/Dashboard/TenantDashboardSectionBuilder.php | V2

namespace App\Support\Dashboard;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use Illuminate\Support\Collection;

class TenantDashboardSectionBuilder
{
    public static function for(Tenant $tenant, User $user): Collection
    {
        return app(TenantDashboardResolver::class)->forTenantUser($tenant, $user);
    }

    public static function makeForTenantUser(Tenant $tenant, User $user): Collection
    {
        return static::for($tenant, $user);
    }

    public static function make(array $payload): Collection
    {
        return static::fromResolvedMatrix($payload);
    }

    public static function matrix(): array
    {
        return [
            [
                'key' => 'daily',
                'order' => 10,
                'type' => 'cards',
                'title' => 'Operación diaria',
                'text' => 'Accesos principales para el trabajo cotidiano.',
                'variant' => 'premium',
                'items' => [
                    [
                        'key' => 'daily.summary',
                        'type' => 'info',
                        'order' => 10,
                        'module' => ModuleCatalog::TASKS,
                        'requires' => [
                            ['type' => 'analytics'],
                        ],
                        'title' => 'Resumen operativo',
                        'text' => 'Lectura rápida del trabajo visible para tu usuario.',
                        'meta' => ':visible_tasks_count tareas visibles',
                    ],
                    [
                        'key' => 'daily.appointments',
                        'type' => 'action',
                        'order' => 20,
                        'module' => ModuleCatalog::APPOINTMENTS,
                        'route' => 'appointments.calendar',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::APPOINTMENTS.'.viewAny'],
                        ],
                        'title' => 'Agenda',
                        'text' => 'Ver calendario mensual y administrar turnos',
                        'meta' => 'Calendario operativo',
                    ],
                    [
                        'key' => 'daily.parties',
                        'type' => 'action',
                        'order' => 30,
                        'module' => ModuleCatalog::PARTIES,
                        'route' => 'parties.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::PARTIES.'.viewAny'],
                        ],
                        'title' => 'Contactos',
                        'text' => 'Ver y administrar contactos',
                        'meta' => ':parties_count contactos',
                    ],
                    [
                        'key' => 'daily.assets',
                        'type' => 'action',
                        'order' => 40,
                        'module' => ModuleCatalog::ASSETS,
                        'route' => 'assets.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::ASSETS.'.viewAny'],
                        ],
                        'title' => 'Activos',
                        'text' => 'Ver y administrar activos operativos',
                        'meta' => ':assets_count activos',
                    ],
                ],
            ],
            [
                'key' => 'service',
                'order' => 20,
                'type' => 'cards',
                'title' => 'Servicio y mantenimiento',
                'text' => 'Accesos automatizados para trabajos técnicos, servicios y mantenimiento. Las acciones disponibles dependen de los permisos configurados para órdenes de servicio.',
                'variant' => 'premium',
                'items' => [
                    [
                        'key' => 'service.orders.index',
                        'type' => 'action',
                        'order' => 10,
                        'module' => ModuleCatalog::SERVICE_MAINTENANCE,
                        'route' => 'service.index',
                        'requires' => [
                            ['type' => 'module_enabled', 'module' => ModuleCatalog::SERVICE_MAINTENANCE],
                            ['type' => 'ability', 'ability' => ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny'],
                            ['type' => 'ability', 'ability' => ModuleCatalog::ORDERS.'.viewAny'],
                        ],
                        'title' => 'Órdenes de servicio',
                        'text' => 'Ver trabajos técnicos, intervenciones y órdenes de mantenimiento',
                        'meta' => ':service_orders_count órdenes de servicio',
                    ],
                    [
                        'key' => 'service.orders.create',
                        'type' => 'action',
                        'order' => 20,
                        'module' => ModuleCatalog::SERVICE_MAINTENANCE,
                        'route' => 'service.orders.create',
                        'requires' => [
                            ['type' => 'module_enabled', 'module' => ModuleCatalog::SERVICE_MAINTENANCE],
                            ['type' => 'ability', 'ability' => ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny'],
                            [
                                'type' => 'ability',
                                'ability' => ModuleCatalog::ORDERS.'.create',
                                'subject' => \App\Models\Order::class,
                                'context' => ['kind' => OrderCatalog::GROUP_SERVICE],
                            ],
                        ],
                        'title' => 'Nueva orden de servicio',
                        'text' => 'Crear una orden de servicio sin configurar el tipo manualmente',
                        'meta' => 'Tipo Servicio preseleccionado',
                    ],
                ],
            ],
            [
                'key' => 'production',
                'order' => 30,
                'type' => 'cards',
                'title' => 'Producción',
                'text' => 'Acceso operativo a órdenes de producción, recetas y contrato material. Las órdenes siguen siendo gestionadas por Orders, con materiales desde Inventory y composición desde Products.',
                'variant' => 'premium',
                'items' => [
                    [
                        'key' => 'production.orders.index',
                        'type' => 'action',
                        'order' => 10,
                        'module' => ModuleCatalog::PRODUCTION,
                        'route' => 'production.index',
                        'requires' => [
                            ['type' => 'module_enabled', 'module' => ModuleCatalog::PRODUCTION],
                            ['type' => 'ability', 'ability' => ModuleCatalog::PRODUCTION.'.viewAny'],
                            ['type' => 'ability', 'ability' => ModuleCatalog::ORDERS.'.viewAny'],
                        ],
                        'title' => 'Órdenes de producción',
                        'text' => 'Ver producción, recetas, entregas de materiales y cierres operativos',
                        'meta' => ':production_orders_count órdenes de producción',
                    ],
                    [
                        'key' => 'production.orders.create',
                        'type' => 'action',
                        'order' => 20,
                        'module' => ModuleCatalog::PRODUCTION,
                        'route' => 'production.orders.create',
                        'requires' => [
                            ['type' => 'module_enabled', 'module' => ModuleCatalog::PRODUCTION],
                            ['type' => 'ability', 'ability' => ModuleCatalog::PRODUCTION.'.viewAny'],
                            [
                                'type' => 'ability',
                                'ability' => ModuleCatalog::ORDERS.'.create',
                                'subject' => \App\Models\Order::class,
                                'context' => ['kind' => OrderCatalog::GROUP_PRODUCTION],
                            ],
                        ],
                        'title' => 'Nueva orden de producción',
                        'text' => 'Crear una orden de producción sin configurar el tipo manualmente',
                        'meta' => 'Tipo Producción preseleccionado',
                    ],
                ],
            ],
            [
                'key' => 'management',
                'order' => 40,
                'type' => 'cards',
                'title' => 'Gestión complementaria',
                'text' => 'Módulos de seguimiento interno, planificación y soporte.',
                'variant' => 'premium',
                'items' => [
                    [
                        'key' => 'management.orders',
                        'type' => 'action',
                        'order' => 10,
                        'module' => ModuleCatalog::ORDERS,
                        'route' => 'orders.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::ORDERS.'.viewAny'],
                        ],
                        'title' => 'Órdenes',
                        'text' => 'Ver y administrar órdenes',
                        'meta' => ':orders_count órdenes',
                    ],
                    [
                        'key' => 'management.tasks',
                        'type' => 'action',
                        'order' => 20,
                        'module' => ModuleCatalog::TASKS,
                        'route' => 'tasks.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::TASKS.'.viewAny'],
                        ],
                        'title' => 'Tareas',
                        'text' => 'Ver y administrar tareas',
                        'meta' => 'Trabajo diario',
                    ],
                    [
                        'key' => 'management.projects',
                        'type' => 'action',
                        'order' => 30,
                        'module' => ModuleCatalog::PROJECTS,
                        'route' => 'projects.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::PROJECTS.'.viewAny'],
                        ],
                        'title' => 'Proyectos',
                        'text' => 'Ver y administrar proyectos',
                        'meta' => 'Seguimiento operativo',
                    ],
                    [
                        'key' => 'management.products',
                        'type' => 'action',
                        'order' => 40,
                        'module' => ModuleCatalog::PRODUCTS,
                        'route' => 'products.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::PRODUCTS.'.viewAny'],
                        ],
                        'title' => 'Productos',
                        'text' => 'Ver y administrar productos y servicios',
                        'meta' => ':products_count productos',
                    ],
                    [
                        'key' => 'management.shops',
                        'type' => 'action',
                        'order' => 50,
                        'module' => ModuleCatalog::SHOPS,
                        'route' => 'shops.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::SHOPS.'.viewAny'],
                        ],
                        'title' => 'Tiendas',
                        'text' => 'Configurá las tiendas internas que publican catálogo hacia la tienda externa.',
                        'meta' => ':shops_count tiendas',
                    ],
                    [
                        'key' => 'management.inventory',
                        'type' => 'action',
                        'order' => 60,
                        'module' => ModuleCatalog::INVENTORY,
                        'route' => 'inventory.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::INVENTORY.'.viewAny'],
                        ],
                        'title' => 'Inventario',
                        'text' => 'Ver saldos por producto y abrir fichas operativas',
                        'meta' => 'Stock y movimientos',
                    ],
                    [
                        'key' => 'management.documents',
                        'type' => 'action',
                        'order' => 70,
                        'module' => ModuleCatalog::DOCUMENTS,
                        'route' => 'documents.index',
                        'requires' => [
                            ['type' => 'ability', 'ability' => ModuleCatalog::DOCUMENTS.'.viewAny'],
                        ],
                        'title' => 'Documentos',
                        'text' => 'Ver y administrar documentos comerciales',
                        'meta' => ':documents_count documentos',
                    ],
                ],
            ],
            [
                'key' => 'project_operational_analysis',
                'order' => 50,
                'type' => 'partial',
                'requires' => [
                    ['type' => 'analytics'],
                ],
                'partial' => 'projects.partials.operational-analysis',
                'payload' => [
                    'projectOverview' => 'project_overview',
                    'taskOverview' => 'task_overview',
                ],
            ],
        ];
    }

    public static function fromResolvedMatrix(array $sections): Collection
    {
        return collect($sections)
            ->map(fn (array $section) => self::normalizeSection($section))
            ->filter()
            ->sortBy('order')
            ->values();
    }

    private static function normalizeSection(array $section): ?array
    {
        if (($section['visible'] ?? true) !== true) {
            return null;
        }

        if (($section['type'] ?? null) === 'partial') {
            return [
                'key' => $section['key'],
                'order' => $section['order'],
                'type' => 'partial',
                'partial' => $section['partial'],
                'payload' => $section['payload'] ?? [],
            ];
        }

        if (($section['type'] ?? null) !== 'cards') {
            return null;
        }

        $items = collect($section['items'] ?? [])
            ->filter(fn (array $item) => ($item['visible'] ?? true) === true)
            ->sortBy('order')
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'key' => $section['key'],
            'order' => $section['order'],
            'type' => 'cards',
            'title' => $section['title'],
            'text' => $section['text'],
            'variant' => $section['variant'] ?? 'premium',
            'info_cards' => $items->where('type', 'info')->values(),
            'action_cards' => $items->where('type', 'action')->values(),
        ];
    }
}
