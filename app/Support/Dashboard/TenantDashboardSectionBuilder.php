<?php

// FILE: app/Support/Dashboard/TenantDashboardSectionBuilder.php | V2

namespace App\Support\Dashboard;

use App\Models\Order;
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
        $items = static::items();

        return collect(static::sections())
            ->filter(fn (array $section) => ($section['enabled'] ?? true) !== false)
            ->map(function (array $section, string $sectionKey) use ($items) {
                $section['key'] = $sectionKey;

                if (($section['type'] ?? null) !== 'cards') {
                    unset($section['enabled']);

                    return $section;
                }

                $section['items'] = collect($section['items'] ?? [])
                    ->map(function (string $itemKey) use ($items) {
                        $item = $items[$itemKey] ?? null;

                        if (! is_array($item) || ($item['enabled'] ?? true) === false) {
                            return null;
                        }

                        $item['key'] = $itemKey;
                        unset($item['enabled']);

                        return $item;
                    })
                    ->filter()
                    ->sortBy('order')
                    ->values()
                    ->all();

                unset($section['enabled']);

                return $section;
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    private static function sections(): array
    {
        return [
            'daily' => [
                'enabled' => true,
                'order' => 10,
                'type' => 'cards',
                'title' => 'Operación diaria',
                'text' => 'Accesos principales para el trabajo cotidiano.',
                'variant' => 'premium',
                'items' => [
                    'operational.summary',
                    'appointments.calendar',
                    'parties.index',
                    'assets.index',
                ],
            ],
            'service' => [
                'enabled' => true,
                'order' => 20,
                'type' => 'cards',
                'title' => 'Servicio y mantenimiento',
                'text' => 'Accesos automatizados para trabajos técnicos, servicios y mantenimiento. Las acciones disponibles dependen de los permisos configurados para órdenes de servicio.',
                'variant' => 'premium',
                'items' => [
                    'service.orders.index',
                    'service.orders.create',
                ],
            ],
            'production' => [
                'enabled' => true,
                'order' => 30,
                'type' => 'cards',
                'title' => 'Producción',
                'text' => 'Acceso operativo a órdenes de producción, recetas y contrato material. Las órdenes siguen siendo gestionadas por Orders, con materiales desde Inventory y composición desde Products.',
                'variant' => 'premium',
                'items' => [
                    'production.orders.index',
                    'production.orders.create',
                ],
            ],
            'management' => [
                'enabled' => true,
                'order' => 50,
                'type' => 'cards',
                'title' => 'Gestión complementaria',
                'text' => 'Módulos de seguimiento interno, planificación y soporte.',
                'variant' => 'premium',
                'items' => [
                    'orders.index',
                    'tasks.index',
                    'projects.index',
                    'products.index',
                    'inventory.index',
                    'documents.index',
                ],
            ],
            'shop' => [
                'enabled' => true,
                'order' => 40,
                'type' => 'cards',
                'title' => 'Tienda',
                'text' => 'Administración interna de tiendas y acceso al catálogo público publicado para el tenant.',
                'variant' => 'premium',
                'requires' => [
                    ['type' => 'module_enabled', 'module' => ModuleCatalog::SHOPS],
                    ['type' => 'ability', 'ability' => ModuleCatalog::SHOPS.'.viewAny'],
                ],
                'items' => [
                    'shops.index',
                    'shops.active.edit',
                    'self_service_sales.shop',
                ],
            ],
            'project_operational_analysis' => [
                'enabled' => true,
                'order' => 60,
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

    private static function items(): array
    {
        return [
            'operational.summary' => [
                'enabled' => true,
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
            'appointments.calendar' => [
                'enabled' => true,
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
            'parties.index' => [
                'enabled' => true,
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
            'assets.index' => [
                'enabled' => true,
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
            'service.orders.index' => [
                'enabled' => true,
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
            'service.orders.create' => [
                'enabled' => true,
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
                        'subject' => Order::class,
                        'context' => ['kind' => OrderCatalog::GROUP_SERVICE],
                    ],
                ],
                'title' => 'Nueva orden de servicio',
                'text' => 'Crear una orden de servicio sin configurar el tipo manualmente',
                'meta' => 'Tipo Servicio preseleccionado',
            ],
            'production.orders.index' => [
                'enabled' => true,
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
            'production.orders.create' => [
                'enabled' => true,
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
                        'subject' => Order::class,
                        'context' => ['kind' => OrderCatalog::GROUP_PRODUCTION],
                    ],
                ],
                'title' => 'Nueva orden de producción',
                'text' => 'Crear una orden de producción sin configurar el tipo manualmente',
                'meta' => 'Tipo Producción preseleccionado',
            ],
            'orders.index' => [
                'enabled' => true,
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
            'tasks.index' => [
                'enabled' => true,
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
            'projects.index' => [
                'enabled' => true,
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
            'products.index' => [
                'enabled' => true,
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
            'shops.index' => [
                'enabled' => true,
                'type' => 'action',
                'order' => 50,
                'module' => ModuleCatalog::SHOPS,
                'route' => 'shops.index',
                'requires' => [
                    ['type' => 'ability', 'ability' => ModuleCatalog::SHOPS.'.viewAny'],
                ],
                'title' => 'Gestionar tiendas',
                'text' => 'Configurar tiendas internas, catálogo publicado y puntos de consumo.',
                'meta' => ':shops_count tiendas',
            ],
            'self_service_sales.shop' => [
                'enabled' => true,
                'type' => 'action',
                'order' => 99,
                'module' => ModuleCatalog::SHOPS,
                'route' => [
                    'name' => 'self_service_sales.shop',
                    'parameters' => [
                        'tenant' => ':tenant',
                    ],
                ],
                'requires' => [
                    ['type' => 'active_shop'],
                ],
                'title' => 'Abrir tienda externa',
                'text' => 'Abrir la tienda pública externa publicada para este tenant.',
                'meta' => ':active_shop_name',
            ],
            'shops.active.edit' => [
                'enabled' => true,
                'type' => 'action',
                'order' => 60,
                'module' => ModuleCatalog::SHOPS,
                'route' => [
                    'name' => 'shops.show',
                    'parameters' => [
                        'shop' => ':active_shop',
                    ],
                ],
                'requires' => [
                    ['type' => 'active_shop'],
                    [
                        'type' => 'ability',
                        'ability' => ModuleCatalog::SHOPS.'.view',
                        'subject' => ':active_shop',
                    ],
                ],
                'title' => 'Editar tienda activa',
                'text' => 'Abrir el perfil interno de la tienda activa publicada para este tenant.',
                'meta' => ':active_shop_name',
            ],
            'inventory.index' => [
                'enabled' => true,
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
            'documents.index' => [
                'enabled' => true,
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
