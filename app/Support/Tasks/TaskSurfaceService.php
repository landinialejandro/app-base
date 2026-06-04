<?php

// FILE: app/Support/Tasks/TaskSurfaceService.php | V10

namespace App\Support\Tasks;

use App\Models\Order;
use App\Models\Project;
use App\Models\Task;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Support\Collection;

class TaskSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'tasks.project.embedded',
                label: 'Tareas',
                targets: ['projects.show'],
                slot: 'tab_panels',
                priority: 10,
                view: 'tasks.partials.embedded-tabs',
                resolver: $this->resolveEmbeddedForProject(...),
            ),
            $this->linkedOffer(
                key: 'task.order.linked',
                label: 'Tarea',
                targets: ['orders.show'],
                slot: 'detail_items',
                priority: 20,
                view: 'tasks.components.linked-task',
                resolver: $this->resolveLinkedForOrder(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return match (true) {
            $host === 'tasks.show' && $record instanceof Task => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'task',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            $host === 'orders.show' && $record instanceof Order => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'order',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            $host === 'projects.show' && $record instanceof Project => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'project',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            default => [],
        };
    }

    private function resolveEmbeddedForProject(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'project' || ! $record instanceof Project) {
            return [
                'count' => 0,
                'data' => $this->projectEmbeddedData(
                    tasks: collect(),
                    projectId: null,
                    trailQuery: $trailQuery,
                ),
            ];
        }

        $tasks = $this->tasksForProject($record);

        return [
            'count' => $tasks->count(),
            'data' => $this->projectEmbeddedData(
                tasks: $tasks,
                projectId: $record->getKey(),
                trailQuery: $trailQuery,
            ),
        ];
    }

    private function projectEmbeddedData(Collection $tasks, ?int $projectId, array $trailQuery): array
    {
        return [
            'tasks' => $tasks,
            'openTasks' => $tasks
                ->whereIn('status', [
                    TaskCatalog::STATUS_PENDING,
                    TaskCatalog::STATUS_IN_PROGRESS,
                ])
                ->values(),
            'doneTasks' => $tasks
                ->where('status', TaskCatalog::STATUS_DONE)
                ->values(),
            'emptyMessageOpen' => 'No hay tareas abiertas en este proyecto.',
            'emptyMessageDone' => 'No hay tareas finalizadas en este proyecto.',
            'emptyMessageAll' => 'No hay tareas asociadas a este proyecto.',
            'supportsOrdersModule' => $this->supportsOrdersModule(),
            'tabsId' => 'project-tasks-tabs',
            'createBaseQuery' => $projectId ? ['project_id' => $projectId] : [],
            'trailQuery' => $trailQuery,
        ];
    }

    private function tasksForProject(Project $project): Collection
    {
        if ($project->relationLoaded('tasks')) {
            $tasks = $project->getRelation('tasks');

            return $tasks instanceof Collection ? $tasks : collect($tasks);
        }

        return $project->tasks()
            ->with(['project', 'assignedUser', 'order'])
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();
    }

    private function supportsOrdersModule(): bool
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        return TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
    }

    private function resolveLinkedForOrder(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'data' => [
                    'linked' => [
                        'supported' => false,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'label' => 'Tarea',
                        'text' => '—',
                        'show_url' => null,
                    ],
                ],
            ];
        }

        $task = Task::query()
            ->where('tenant_id', $record->tenant_id)
            ->where('order_id', $record->id)
            ->orderBy('due_date')
            ->orderBy('name')
            ->first();

        return [
            'data' => [
                'linked' => TaskLinked::forTask(
                    $task,
                    $trailQuery,
                    'Tarea',
                ),
            ],
        ];
    }
}
