<?php

// FILE: app/Http/Controllers/DashboardController.php | V5

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
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
        $resolver = app(RolePermissionResolver::class);

        $visibleProjects = ProjectVisibility::visibleQuery($tenant, $user)->get([
            'projects.id',
            'projects.status',
        ]);

        $visibleTasks = TaskVisibility::visibleQuery($tenant, $user)->get([
            'tasks.id',
            'tasks.project_id',
            'tasks.assigned_user_id',
            'tasks.status',
            'tasks.due_date',
        ]);

        $today = now()->startOfDay();

        $visibleProjectsCount = $visibleProjects->count();
        $activeProjectsCount = $visibleProjects->where('status', ProjectCatalog::STATUS_ACTIVE)->count();
        $closedProjectsCount = $visibleProjects->where('status', ProjectCatalog::STATUS_CLOSED)->count();

        $tasksGroupedByProject = $visibleTasks->groupBy('project_id');

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

        $canAccessAppointments = $resolver->canUseModule(ModuleCatalog::APPOINTMENTS, $tenant, $user);
        $canAccessParties = $resolver->canUseModule(ModuleCatalog::PARTIES, $tenant, $user);
        $canAccessAssets = $resolver->canUseModule(ModuleCatalog::ASSETS, $tenant, $user);
        $canAccessOrders = $resolver->canUseModule(ModuleCatalog::ORDERS, $tenant, $user);
        $canAccessTasks = $resolver->canUseModule(ModuleCatalog::TASKS, $tenant, $user);
        $canAccessProjects = $resolver->canUseModule(ModuleCatalog::PROJECTS, $tenant, $user);
        $canAccessProducts = $resolver->canUseModule(ModuleCatalog::PRODUCTS, $tenant, $user);
        $canAccessDocuments = $resolver->canUseModule(ModuleCatalog::DOCUMENTS, $tenant, $user);

        return view('dashboard', [
            'tenant' => $tenant,

            'canSeeAnalytics' => $resolver->can(
                ModuleCatalog::DASHBOARD,
                CapabilityCatalog::VIEW_ANALYTICS,
                $tenant,
                $user
            ),

            'canAccessAppointments' => $canAccessAppointments,
            'canAccessParties' => $canAccessParties,
            'canAccessAssets' => $canAccessAssets,
            'canAccessOrders' => $canAccessOrders,
            'canAccessTasks' => $canAccessTasks,
            'canAccessProjects' => $canAccessProjects,
            'canAccessProducts' => $canAccessProducts,
            'canAccessDocuments' => $canAccessDocuments,

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

            'partiesCount' => $canAccessParties ? Party::query()->count() : null,
            'productsCount' => $canAccessProducts ? Product::query()->count() : null,
            'assetsCount' => $canAccessAssets ? Asset::query()->count() : null,
            'ordersCount' => $canAccessOrders ? Order::query()->count() : null,
            'documentsCount' => $canAccessDocuments ? Document::query()->count() : null,
        ]);
    }
}
