<?php

// FILE: app/Support/Tasks/TaskSurfaceService.php | V9

namespace App\Support\Tasks;

use App\Models\Order;
use App\Models\Task;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class TaskSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
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
            default => [],
        };
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

        $task = $record->relationLoaded('tasks')
            ? $record->tasks->sortBy('due_date')->sortBy('name')->first()
            : $record->tasks()->orderBy('due_date')->orderBy('name')->first();

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