<?php

namespace App\Support\Tasks;

use App\Models\User;
use App\Support\Catalogs\TaskCatalog;

class TaskOverview
{
    public static function make(?User $user = null): array
    {
        $visibleTasks = TaskVisibility::visibleQuery($user)->get();
        $myTasks = TaskVisibility::mineQuery($user)->get();

        $overdueCount = $visibleTasks->filter(function ($task) {
            return $task->due_date &&
                $task->due_date->isPast() &&
                ! in_array($task->status, [TaskCatalog::STATUS_DONE, TaskCatalog::STATUS_CANCELLED], true);
        })->count();

        $myOverdueCount = $myTasks->filter(function ($task) {
            return $task->due_date &&
                $task->due_date->isPast() &&
                ! in_array($task->status, [TaskCatalog::STATUS_DONE, TaskCatalog::STATUS_CANCELLED], true);
        })->count();

        return [
            'visible_tasks_count' => $visibleTasks->count(),
            'my_tasks_count' => $myTasks->count(),

            'pending_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_PENDING)->count(),
            'in_progress_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_IN_PROGRESS)->count(),
            'done_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_DONE)->count(),
            'cancelled_tasks_count' => $visibleTasks->where('status', TaskCatalog::STATUS_CANCELLED)->count(),
            'overdue_tasks_count' => $overdueCount,

            'my_pending_tasks_count' => $myTasks->where('status', TaskCatalog::STATUS_PENDING)->count(),
            'my_in_progress_tasks_count' => $myTasks->where('status', TaskCatalog::STATUS_IN_PROGRESS)->count(),
            'my_done_tasks_count' => $myTasks->where('status', TaskCatalog::STATUS_DONE)->count(),
            'my_cancelled_tasks_count' => $myTasks->where('status', TaskCatalog::STATUS_CANCELLED)->count(),
            'my_overdue_tasks_count' => $myOverdueCount,
        ];
    }
}
