<?php

// FILE: app/Support/Tasks/TaskLinkedAction.php | V1

namespace App\Support\Tasks;

use App\Models\Task;

class TaskLinkedAction
{
    public static function forTask(?Task $task, array $trailQuery = [], string $label = 'Tarea'): array
    {
        if (! $task) {
            return [
                'supported' => true,
                'linked' => false,
                'can_view' => false,
                'hidden' => false,
                'label' => $label,
                'text' => '—',
                'show_url' => null,
                'trail_query' => $trailQuery,
            ];
        }

        $user = auth()->user();
        $canView = $user && $user->can('view', $task);

        return [
            'supported' => true,
            'linked' => true,
            'can_view' => (bool) $canView,
            'hidden' => false,
            'label' => $label,
            'text' => $task->name ?: 'Tarea #'.$task->id,
            'show_url' => $canView ? route('tasks.show', ['task' => $task] + $trailQuery) : null,
            'trail_query' => $trailQuery,
        ];
    }
}
