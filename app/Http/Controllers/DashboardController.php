<?php

// FILE: app/Http/Controllers/DashboardController.php | V14

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Shop;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProjectCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Dashboard\TenantDashboardSectionBuilder;
use App\Support\Projects\ProjectVisibility;
use App\Support\Tasks\TaskVisibility;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $user = auth()->user();
        $security = app(Security::class);

        $membership = Membership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        $visibleProjects = ProjectVisibility::visibleQuery(null, $tenant, $user)
            ->get(['projects.id', 'projects.status']);

        $visibleTasks = TaskVisibility::visibleQuery(null, $tenant, $user)
            ->get([
                'tasks.id',
                'tasks.project_id',
                'tasks.assigned_user_id',
                'tasks.status',
                'tasks.due_date',
            ]);

        $today = now()->startOfDay();

        $tasksGroupedByProject = $visibleTasks->groupBy('project_id');

        $visibleProjectsCount = $visibleProjects->count();
        $activeProjectsCount = $visibleProjects->where('status', ProjectCatalog::STATUS_ACTIVE)->count();
        $closedProjectsCount = $visibleProjects->where('status', ProjectCatalog::STATUS_CLOSED)->count();

        $projectsWithOpenTasksCount = $visibleProjects
            ->filter(function ($project) use ($tasksGroupedByProject) {
                $tasks = $tasksGroupedByProject->get($project->id, collect());

                return $tasks->contains(function ($task) {
                    return in_array($task->status, [
                        TaskCatalog::STATUS_PENDING,
                        TaskCatalog::STATUS_IN_PROGRESS,
                    ], true);
                });
            })
            ->count();

        $projectsWithOverdueTasksCount = $visibleProjects
            ->filter(function ($project) use ($tasksGroupedByProject, $today) {
                $tasks = $tasksGroupedByProject->get($project->id, collect());

                return $tasks->contains(function ($task) use ($today) {
                    return $task->due_date
                        && $task->due_date->copy()->startOfDay()->lt($today)
                        && ! in_array($task->status, [
                            TaskCatalog::STATUS_DONE,
                            TaskCatalog::STATUS_CANCELLED,
                        ], true);
                });
            })
            ->count();

        $projectProgressValues = $visibleProjects->map(function ($project) use ($tasksGroupedByProject) {
            $tasks = $tasksGroupedByProject->get($project->id, collect());
            $total = $tasks->count();

            if ($total === 0) {
                return 0;
            }

            $done = $tasks->where('status', TaskCatalog::STATUS_DONE)->count();

            return round(($done / $total) * 100);
        });

        $projectsAverageProgress = $projectProgressValues->count() > 0
            ? (int) round($projectProgressValues->avg())
            : 0;

        $visibleTasksCount = $visibleTasks->count();
        $myTasksCount = $visibleTasks->where('assigned_user_id', $user->id)->count();
        $pendingTasksCount = $visibleTasks->where('status', TaskCatalog::STATUS_PENDING)->count();
        $inProgressTasksCount = $visibleTasks->where('status', TaskCatalog::STATUS_IN_PROGRESS)->count();
        $doneTasksCount = $visibleTasks->where('status', TaskCatalog::STATUS_DONE)->count();
        $cancelledTasksCount = $visibleTasks->where('status', TaskCatalog::STATUS_CANCELLED)->count();

        $myOverdueTasksCount = $visibleTasks
            ->filter(function ($task) use ($user, $today) {
                return (int) $task->assigned_user_id === (int) $user->id
                    && $task->due_date
                    && $task->due_date->copy()->startOfDay()->lt($today)
                    && ! in_array($task->status, [
                        TaskCatalog::STATUS_DONE,
                        TaskCatalog::STATUS_CANCELLED,
                    ], true);
            })
            ->count();

        $canAccessAppointments = $security->allows($user, ModuleCatalog::APPOINTMENTS.'.viewAny');
        $canAccessParties = $security->allows($user, ModuleCatalog::PARTIES.'.viewAny');
        $canAccessAssets = $security->allows($user, ModuleCatalog::ASSETS.'.viewAny');
        $canAccessOrders = $security->allows($user, ModuleCatalog::ORDERS.'.viewAny');
        $canAccessTasks = $security->allows($user, ModuleCatalog::TASKS.'.viewAny');
        $canAccessProjects = $security->allows($user, ModuleCatalog::PROJECTS.'.viewAny');
        $canAccessProducts = $security->allows($user, ModuleCatalog::PRODUCTS.'.viewAny');
        $canAccessShops = $security->allows($user, ModuleCatalog::SHOPS.'.viewAny');
        $canAccessDocuments = $security->allows($user, ModuleCatalog::DOCUMENTS.'.viewAny');
        $canAccessInventory = $security->allows($user, ModuleCatalog::INVENTORY.'.viewAny');

        $serviceMaintenanceEnabled = TenantModuleAccess::isEnabled(ModuleCatalog::SERVICE_MAINTENANCE, $tenant);
        $productionEnabled = TenantModuleAccess::isEnabled(ModuleCatalog::PRODUCTION, $tenant);

        $canAccessServiceMaintenance = $serviceMaintenanceEnabled
            && $security->allows($user, ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny');

        $canAccessProduction = $productionEnabled
            && $security->allows($user, ModuleCatalog::PRODUCTION.'.viewAny');

        $canViewServiceOrders = $canAccessServiceMaintenance
            && $security->allows($user, ModuleCatalog::ORDERS.'.viewAny');

        $canCreateServiceOrders = $canAccessServiceMaintenance
            && $security->allows(
                $user,
                ModuleCatalog::ORDERS.'.create',
                Order::class,
                ['kind' => OrderCatalog::GROUP_SERVICE]
            );

        $serviceOrdersCount = $canViewServiceOrders
            ? $security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_SERVICE)
                ->count()
            : null;

        $canViewProductionOrders = $canAccessProduction
            && $security->allows($user, ModuleCatalog::ORDERS.'.viewAny');

        $canCreateProductionOrders = $canAccessProduction
            && $security->allows(
                $user,
                ModuleCatalog::ORDERS.'.create',
                Order::class,
                ['kind' => OrderCatalog::GROUP_PRODUCTION]
            );

        $productionOrdersCount = $canViewProductionOrders
            ? $security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_PRODUCTION)
                ->count()
            : null;

        $canSeeAnalytics = ($membership?->is_owner === true)
            || $security->allows($user, ModuleCatalog::DASHBOARD.'.viewAny');

        $partiesCount = $canAccessParties
            ? $security->scope($user, ModuleCatalog::PARTIES.'.viewAny', Party::query())->count()
            : null;

        $productsCount = $canAccessProducts
            ? $security->scope($user, ModuleCatalog::PRODUCTS.'.viewAny', Product::query())->count()
            : null;

        $shopsCount = $canAccessShops
            ? $security->scope($user, ModuleCatalog::SHOPS.'.viewAny', Shop::query())->count()
            : null;

        $assetsCount = $canAccessAssets
            ? $security->scope($user, ModuleCatalog::ASSETS.'.viewAny', Asset::query())->count()
            : null;

        $ordersCount = $canAccessOrders
            ? $security->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())->count()
            : null;

        $documentsCount = $canAccessDocuments
            ? $security->scope($user, ModuleCatalog::DOCUMENTS.'.viewAny', Document::query())->count()
            : null;

        $dailyInfoCards = $this->visibleDashboardInfoCards([
            [
                'module' => ModuleCatalog::TASKS,
                'can' => $canSeeAnalytics,
                'title' => 'Resumen operativo',
                'text' => 'Lectura rápida del trabajo visible para tu usuario.',
                'meta' => $visibleTasksCount . ' tareas visibles',
            ],
        ]);

        $serviceMaintenanceInfoCards = collect();
        $productionInfoCards = collect();
        $managementInfoCards = collect();

        $dailyCards = $this->visibleDashboardCards([
            [
                'module' => ModuleCatalog::APPOINTMENTS,
                'can' => $canAccessAppointments,
                'route' => route('appointments.calendar'),
                'title' => 'Agenda',
                'text' => 'Ver calendario mensual y administrar turnos',
                'meta' => 'Calendario operativo',
            ],
            [
                'module' => ModuleCatalog::PARTIES,
                'can' => $canAccessParties,
                'route' => route('parties.index'),
                'title' => 'Contactos',
                'text' => 'Ver y administrar contactos',
                'meta' => $partiesCount . ' contactos',
            ],
            [
                'module' => ModuleCatalog::ASSETS,
                'can' => $canAccessAssets,
                'route' => route('assets.index'),
                'title' => 'Activos',
                'text' => 'Ver y administrar activos operativos',
                'meta' => $assetsCount . ' activos',
            ],
        ]);

        $serviceMaintenanceCards = $this->visibleDashboardCards([
            [
                'module' => ModuleCatalog::SERVICE_MAINTENANCE,
                'can' => $canViewServiceOrders,
                'route' => route('service.index'),
                'title' => 'Órdenes de servicio',
                'text' => 'Ver trabajos técnicos, intervenciones y órdenes de mantenimiento',
                'meta' => ($serviceOrdersCount ?? 0) . ' órdenes de servicio',
            ],
            [
                'module' => ModuleCatalog::SERVICE_MAINTENANCE,
                'can' => $canCreateServiceOrders,
                'route' => route('service.orders.create'),
                'title' => 'Nueva orden de servicio',
                'text' => 'Crear una orden de servicio sin configurar el tipo manualmente',
                'meta' => 'Tipo Servicio preseleccionado',
            ],
        ]);

        $productionCards = $this->visibleDashboardCards([
            [
                'module' => ModuleCatalog::PRODUCTION,
                'can' => $canViewProductionOrders,
                'route' => route('production.index'),
                'title' => 'Órdenes de producción',
                'text' => 'Ver producción, recetas, entregas de materiales y cierres operativos',
                'meta' => ($productionOrdersCount ?? 0) . ' órdenes de producción',
            ],
            [
                'module' => ModuleCatalog::PRODUCTION,
                'can' => $canCreateProductionOrders,
                'route' => route('production.orders.create'),
                'title' => 'Nueva orden de producción',
                'text' => 'Crear una orden de producción sin configurar el tipo manualmente',
                'meta' => 'Tipo Producción preseleccionado',
            ],
        ]);

        $managementCards = $this->visibleDashboardCards([
            [
                'module' => ModuleCatalog::ORDERS,
                'can' => $canAccessOrders,
                'route' => route('orders.index'),
                'title' => 'Órdenes',
                'text' => 'Ver y administrar órdenes',
                'meta' => $ordersCount . ' órdenes',
            ],
            [
                'module' => ModuleCatalog::TASKS,
                'can' => $canAccessTasks,
                'route' => route('tasks.index'),
                'title' => 'Tareas',
                'text' => 'Ver y administrar tareas',
                'meta' => 'Trabajo diario',
            ],
            [
                'module' => ModuleCatalog::PROJECTS,
                'can' => $canAccessProjects,
                'route' => route('projects.index'),
                'title' => 'Proyectos',
                'text' => 'Ver y administrar proyectos',
                'meta' => 'Seguimiento operativo',
            ],
            [
                'module' => ModuleCatalog::PRODUCTS,
                'can' => $canAccessProducts,
                'route' => route('products.index'),
                'title' => 'Productos',
                'text' => 'Ver y administrar productos y servicios',
                'meta' => $productsCount . ' productos',
            ],
            [
                'module' => ModuleCatalog::SHOPS,
                'can' => $canAccessShops,
                'route' => route('shops.index'),
                'title' => 'Tiendas',
                'text' => 'Configurá las tiendas internas que publican catálogo hacia la tienda externa.',
                'meta' => ($shopsCount ?? 0) . ' tiendas',
            ],
            [
                'module' => ModuleCatalog::INVENTORY,
                'can' => $canAccessInventory,
                'route' => route('inventory.index'),
                'title' => 'Inventario',
                'text' => 'Ver saldos por producto y abrir fichas operativas',
                'meta' => 'Stock y movimientos',
            ],
            [
                'module' => ModuleCatalog::DOCUMENTS,
                'can' => $canAccessDocuments,
                'route' => route('documents.index'),
                'title' => 'Documentos',
                'text' => 'Ver y administrar documentos comerciales',
                'meta' => $documentsCount . ' documentos',
            ],
        ]);

        $projectOverview = [
            'visible_projects_count' => $visibleProjectsCount,
            'active_projects_count' => $activeProjectsCount,
            'closed_projects_count' => $closedProjectsCount,
            'projects_with_open_tasks_count' => $projectsWithOpenTasksCount,
            'projects_with_overdue_tasks_count' => $projectsWithOverdueTasksCount,
            'projects_average_progress' => $projectsAverageProgress,
        ];

        $taskOverview = [
            'visible_tasks_count' => $visibleTasksCount,
            'my_tasks_count' => $myTasksCount,
            'pending_tasks_count' => $pendingTasksCount,
            'in_progress_tasks_count' => $inProgressTasksCount,
            'done_tasks_count' => $doneTasksCount,
            'cancelled_tasks_count' => $cancelledTasksCount,
            'my_overdue_tasks_count' => $myOverdueTasksCount,
        ];

        $dashboardSections = TenantDashboardSectionBuilder::make([
            'dailyInfoCards' => $dailyInfoCards,
            'dailyCards' => $dailyCards,
            'serviceMaintenanceInfoCards' => $serviceMaintenanceInfoCards,
            'serviceMaintenanceCards' => $serviceMaintenanceCards,
            'productionInfoCards' => $productionInfoCards,
            'productionCards' => $productionCards,
            'managementInfoCards' => $managementInfoCards,
            'managementCards' => $managementCards,
            'canSeeAnalytics' => $canSeeAnalytics,
            'projectOverview' => $projectOverview,
            'taskOverview' => $taskOverview,
        ]);

        return view('dashboard', [
            'tenant' => $tenant,
            'dashboardSections' => $dashboardSections,
        ]);
    }

    private function visibleDashboardCards(array $cards)
    {
        return collect($cards)
            ->where('can', true)
            ->map(function (array $card) {
                return [
                    'module' => $card['module'],
                    'icon' => ModuleCatalog::icon($card['module']),
                    'route' => $card['route'],
                    'title' => $card['title'],
                    'text' => $card['text'],
                    'meta' => $card['meta'],
                ];
            })
            ->values();
    }

    private function visibleDashboardInfoCards(array $cards)
    {
        return collect($cards)
            ->where('can', true)
            ->map(function (array $card) {
                return [
                    'module' => $card['module'],
                    'icon' => ModuleCatalog::icon($card['module']),
                    'title' => $card['title'],
                    'text' => $card['text'],
                    'meta' => $card['meta'],
                ];
            })
            ->values();
    }
}
