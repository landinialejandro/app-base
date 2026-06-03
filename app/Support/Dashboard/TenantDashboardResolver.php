<?php

// FILE: app/Support/Dashboard/TenantDashboardResolver.php | V1

namespace App\Support\Dashboard;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Party;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProjectCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Projects\ProjectVisibility;
use App\Support\Tasks\TaskVisibility;
use Illuminate\Support\Collection;

class TenantDashboardResolver
{
    private array $metrics = [];

    private ?Collection $visibleProjects = null;

    private ?Collection $visibleTasks = null;

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function forTenantUser(Tenant $tenant, User $user): Collection
    {
        app()->instance('tenant', $tenant);
        $this->metrics = [];
        $this->visibleProjects = null;
        $this->visibleTasks = null;

        return $this->resolveSections(
            TenantDashboardSectionBuilder::matrix(),
            $tenant,
            $user
        );
    }

    public function resolveSections(array $matrix, Tenant $tenant, User $user): Collection
    {
        $sections = collect($matrix)
            ->map(fn (array $section) => $this->resolveSection($section, $tenant, $user))
            ->filter()
            ->values()
            ->all();

        return TenantDashboardSectionBuilder::fromResolvedMatrix($sections);
    }

    private function resolveSection(array $section, Tenant $tenant, User $user): ?array
    {
        if (! $this->resolveRequirements($section['requires'] ?? [], $tenant, $user)) {
            return null;
        }

        if (($section['type'] ?? null) === 'partial') {
            return [
                'key' => $section['key'],
                'order' => $section['order'],
                'type' => 'partial',
                'visible' => true,
                'partial' => $section['partial'],
                'payload' => $this->resolvePayload($section['payload'] ?? [], $tenant, $user),
            ];
        }

        if (($section['type'] ?? null) !== 'cards') {
            return null;
        }

        return [
            'key' => $section['key'],
            'order' => $section['order'],
            'type' => 'cards',
            'title' => $section['title'],
            'text' => $section['text'],
            'variant' => $section['variant'] ?? 'premium',
            'visible' => true,
            'items' => collect($section['items'] ?? [])
                ->map(fn (array $item) => $this->resolveItem($item, $tenant, $user))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public function resolveRequirements(array $requirements, Tenant $tenant, User $user): bool
    {
        foreach ($requirements as $requirement) {
            if (! $this->resolveRequirement($requirement, $tenant, $user)) {
                return false;
            }
        }

        return true;
    }

    private function resolveRequirement(array $requirement, Tenant $tenant, User $user): bool
    {
        return match ($requirement['type'] ?? null) {
            'module_enabled' => TenantModuleAccess::isEnabled($requirement['module'], $tenant),
            'ability' => $this->security->allows(
                $user,
                $requirement['ability'],
                $requirement['subject'] ?? null,
                $requirement['context'] ?? []
            ),
            'analytics' => $this->canSeeAnalytics($tenant, $user),
            default => false,
        };
    }

    private function resolveItem(array $item, Tenant $tenant, User $user): ?array
    {
        if (! $this->resolveRequirements($item['requires'] ?? [], $tenant, $user)) {
            return null;
        }

        $resolved = [
            'key' => $item['key'],
            'type' => $item['type'],
            'order' => $item['order'],
            'visible' => true,
            'module' => $item['module'],
            'icon' => ModuleCatalog::icon($item['module']),
            'title' => $item['title'],
            'text' => $item['text'],
            'meta' => $this->resolveMeta($item['meta'], $tenant, $user),
        ];

        if (($item['type'] ?? null) === 'action') {
            $resolved['route'] = $this->resolveRoute($item['route']);
        }

        return $resolved;
    }

    private function resolvePayload(array $payload, Tenant $tenant, User $user): array
    {
        return collect($payload)
            ->mapWithKeys(fn (string $metric, string $key) => [
                $key => $this->resolveMetric($metric, $tenant, $user),
            ])
            ->all();
    }

    private function resolveRoute(array|string $route): string
    {
        if (is_array($route)) {
            return route($route['name'], $route['parameters'] ?? []);
        }

        return route($route);
    }

    private function resolveMeta(string $meta, Tenant $tenant, User $user): string
    {
        return preg_replace_callback('/\:([a-z0-9_]+)/', function (array $matches) use ($tenant, $user) {
            return (string) $this->resolveMetric($matches[1], $tenant, $user);
        }, $meta);
    }

    private function resolveMetric(string $key, Tenant $tenant, User $user): mixed
    {
        if (array_key_exists($key, $this->metrics)) {
            return $this->metrics[$key];
        }

        return $this->metrics[$key] = match ($key) {
            'visible_tasks_count' => $this->visibleTasks($tenant, $user)->count(),
            'parties_count' => $this->security->scope($user, ModuleCatalog::PARTIES.'.viewAny', Party::query())->count(),
            'assets_count' => $this->security->scope($user, ModuleCatalog::ASSETS.'.viewAny', Asset::query())->count(),
            'orders_count' => $this->security->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())->count(),
            'products_count' => $this->security->scope($user, ModuleCatalog::PRODUCTS.'.viewAny', Product::query())->count(),
            'shops_count' => $this->security->scope($user, ModuleCatalog::SHOPS.'.viewAny', Shop::query())->count(),
            'documents_count' => $this->security->scope($user, ModuleCatalog::DOCUMENTS.'.viewAny', Document::query())->count(),
            'service_orders_count' => $this->security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_SERVICE)
                ->count(),
            'production_orders_count' => $this->security
                ->scope($user, ModuleCatalog::ORDERS.'.viewAny', Order::query())
                ->where('group', OrderCatalog::GROUP_PRODUCTION)
                ->count(),
            'can_see_analytics' => $this->resolveCanSeeAnalytics($tenant, $user),
            'project_overview' => $this->projectOverview($tenant, $user),
            'task_overview' => $this->taskOverview($tenant, $user),
            default => null,
        };
    }

    private function canSeeAnalytics(Tenant $tenant, User $user): bool
    {
        return (bool) $this->resolveMetric('can_see_analytics', $tenant, $user);
    }

    private function visibleProjects(Tenant $tenant, User $user): Collection
    {
        return $this->visibleProjects ??= ProjectVisibility::visibleQuery(null, $tenant, $user)
            ->get(['projects.id', 'projects.status']);
    }

    private function visibleTasks(Tenant $tenant, User $user): Collection
    {
        return $this->visibleTasks ??= TaskVisibility::visibleQuery(null, $tenant, $user)
            ->get([
                'tasks.id',
                'tasks.project_id',
                'tasks.assigned_user_id',
                'tasks.status',
                'tasks.due_date',
            ]);
    }

    private function projectOverview(Tenant $tenant, User $user): array
    {
        $visibleProjects = $this->visibleProjects($tenant, $user);
        $visibleTasks = $this->visibleTasks($tenant, $user);
        $tasksGroupedByProject = $visibleTasks->groupBy('project_id');
        $today = now()->startOfDay();

        $projectProgressValues = $visibleProjects->map(function ($project) use ($tasksGroupedByProject) {
            $tasks = $tasksGroupedByProject->get($project->id, collect());
            $total = $tasks->count();

            if ($total === 0) {
                return 0;
            }

            $done = $tasks->where('status', TaskCatalog::STATUS_DONE)->count();

            return round(($done / $total) * 100);
        });

        return [
            'visible_projects_count' => $visibleProjects->count(),
            'active_projects_count' => $visibleProjects->where('status', ProjectCatalog::STATUS_ACTIVE)->count(),
            'closed_projects_count' => $visibleProjects->where('status', ProjectCatalog::STATUS_CLOSED)->count(),
            'projects_with_open_tasks_count' => $visibleProjects
                ->filter(function ($project) use ($tasksGroupedByProject) {
                    $tasks = $tasksGroupedByProject->get($project->id, collect());

                    return $tasks->contains(fn ($task) => in_array($task->status, [
                        TaskCatalog::STATUS_PENDING,
                        TaskCatalog::STATUS_IN_PROGRESS,
                    ], true));
                })
                ->count(),
            'projects_with_overdue_tasks_count' => $visibleProjects
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
                ->count(),
            'projects_average_progress' => $projectProgressValues->count() > 0
                ? (int) round($projectProgressValues->avg())
                : 0,
        ];
    }

    private function taskOverview(Tenant $tenant, User $user): array
    {
        $visibleTasks = $this->visibleTasks($tenant, $user);
        $today = now()->startOfDay();

        return [
            'visible_tasks_count' => $visibleTasks->count(),
            'my_tasks_count' => $visibleTasks->where('assigned_user_id', $user->id)->count(),
            'pending_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_PENDING)->count(),
            'in_progress_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_IN_PROGRESS)->count(),
            'done_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_DONE)->count(),
            'cancelled_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_CANCELLED)->count(),
            'my_overdue_tasks_count' => $visibleTasks
                ->filter(function ($task) use ($user, $today) {
                    return (int) $task->assigned_user_id === (int) $user->id
                        && $task->due_date
                        && $task->due_date->copy()->startOfDay()->lt($today)
                        && ! in_array($task->status, [
                            TaskCatalog::STATUS_DONE,
                            TaskCatalog::STATUS_CANCELLED,
                        ], true);
                })
                ->count(),
        ];
    }

    private function resolveCanSeeAnalytics(Tenant $tenant, User $user): bool
    {
        $membership = Membership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        return ($membership?->is_owner === true)
            || $this->security->allows($user, ModuleCatalog::DASHBOARD.'.viewAny');
    }
}
