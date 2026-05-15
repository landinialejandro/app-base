<?php

// FILE: app/Http/Controllers/DashboardController.php | V12

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

        $canAccessServiceMaintenance = $serviceMaintenanceEnabled
            && $security->allows($user, ModuleCatalog::SERVICE_MAINTENANCE.'.viewAny');

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

        $canSeeAnalytics = ($membership?->is_owner === true)
            || $security->allows($user, ModuleCatalog::DASHBOARD.'.viewAny');

        return view('dashboard', [
            'tenant' => $tenant,

            'canSeeAnalytics' => $canSeeAnalytics,

            'canAccessAppointments' => $canAccessAppointments,
            'canAccessParties' => $canAccessParties,
            'canAccessAssets' => $canAccessAssets,
            'canAccessOrders' => $canAccessOrders,
            'canAccessTasks' => $canAccessTasks,
            'canAccessProjects' => $canAccessProjects,
            'canAccessProducts' => $canAccessProducts,
            'canAccessShops' => $canAccessShops,
            'canAccessDocuments' => $canAccessDocuments,
            'canAccessInventory' => $canAccessInventory,

            'canAccessServiceMaintenance' => $canAccessServiceMaintenance,
            'canViewServiceOrders' => $canViewServiceOrders,
            'canCreateServiceOrders' => $canCreateServiceOrders,
            'serviceOrdersCount' => $serviceOrdersCount,

            'projectOverview' => [
                'visible_projects_count' => $visibleProjectsCount,
                'active_projects_count' => $activeProjectsCount,
                'closed_projects_count' => $closedProjectsCount,
                'projects_with_open_tasks_count' => $projectsWithOpenTasksCount,
                'projects_with_overdue_tasks_count' => $projectsWithOverdueTasksCount,
                'projects_average_progress' => $projectsAverageProgress,
            ],

            'taskOverview' => [
                'visible_tasks_count' => $visibleTasksCount,
                'my_tasks_count' => $myTasksCount,
                'pending_tasks_count' => $pendingTasksCount,
                'in_progress_tasks_count' => $inProgressTasksCount,
                'done_tasks_count' => $doneTasksCount,
                'cancelled_tasks_count' => $cancelledTasksCount,
                'my_overdue_tasks_count' => $myOverdueTasksCount,
            ],

            'partiesCount' => $canAccessParties
                ? $security->scope($user, ModuleCatalog::PARTIES.'.viewAny', Party::query())->count()
                : null,

            'productsCount' => $canAccessProducts
                ? $security->scope($user, ModuleCatalog::PRODUCTS.'.viewAny', Product::query())->count()
                : null,

            'shopsCount' => $canAccessShops
                ? $security->scope($user, ModuleCatalog::SHOPS.'.viewAny', Shop::query())->count()
                : null,

            'assetsCount' => $canAccessAssets
                ? $security->scope($user, ModuleCatalog::ASSETS.'.viewAny', Asset::query())->count()
                : null,

            'ordersCount' => $canAccessOrders
                ? $security->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())->count()
                : null,

            'documentsCount' => $canAccessDocuments
                ? $security->scope($user, ModuleCatalog::DOCUMENTS.'.viewAny', Document::query())->count()
                : null,
        ]);
    }
}