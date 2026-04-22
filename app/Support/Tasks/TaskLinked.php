<?php

// FILE: app/Support/Tasks/TaskLinked.php | V1

namespace App\Support\Tasks;

use App\Models\Task;

class TaskLinked
{
    public static function forTask(?Task $task, array $trailQuery = [], string $label = 'Tarea'): array
    {
        if (! $task) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'label' => $label,
                'text' => '—',
                'trail_query' => $trailQuery,
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $task));

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView ? route('tasks.show', ['task' => $task] + $trailQuery) : null,
            'label' => $label,
            'text' => $task->name ?: 'Tarea #'.$task->id,
            'trail_query' => $trailQuery,
        ];
    }
}
