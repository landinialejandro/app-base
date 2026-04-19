<?php

// FILE: app/Support/Tasks/TaskSurfaceService.php | V6

namespace App\Support\Tasks;

use App\Models\Order;
use App\Models\Task;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class TaskSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'embedded',
                'key' => 'task.order.linked',
                'label' => 'Tarea',
                'targets' => ['orders.show'],
                'slot' => 'detail_items',
                'priority' => 20,
                'view' => 'tasks.components.linked-task-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForOrder(...),
            ],
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
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'hidden' => true,
                        'label' => 'Tarea',
                        'text' => '—',
                        'show_url' => null,
                    ],
                ],
            ];
        }

        return [
            'data' => [
                'action' => TaskLinkedAction::forTask(
                    $record->task,
                    $trailQuery,
                    'Tarea',
                ),
            ],
        ];
    }
}
