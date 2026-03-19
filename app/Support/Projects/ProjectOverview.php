<?php

namespace App\Support\Projects;

use App\Models\User;
use App\Support\Catalogs\ProjectCatalog;

class ProjectOverview
{
    public static function make(?User $user = null): array
    {
        $projects = ProjectVisibility::visibleQuery($user)->get();

        $activeCount = $projects->where('status', ProjectCatalog::STATUS_ACTIVE)->count();
        $closedCount = $projects->where('status', ProjectCatalog::STATUS_CLOSED)->count();

        $withOpenTasksCount = 0;
        $withOverdueTasksCount = 0;
        $progressSum = 0;

        foreach ($projects as $project) {
            $tasks = $project->tasks()->get();

            $openTasks = $tasks->whereIn('status', ['pending', 'in_progress']);
            $doneTasks = $tasks->where('status', 'done');

            if ($openTasks->count() > 0) {
                $withOpenTasksCount++;
            }

            $hasOverdue = $tasks->contains(function ($task) {
                return $task->due_date &&
                    $task->due_date->isPast() &&
                    ! in_array($task->status, ['done', 'cancelled'], true);
            });

            if ($hasOverdue) {
                $withOverdueTasksCount++;
            }

            $totalTasks = $tasks->count();
            $progress = $totalTasks > 0 ? round(($doneTasks->count() / $totalTasks) * 100) : 0;
            $progressSum += $progress;
        }

        $averageProgress = $projects->count() > 0
            ? round($progressSum / $projects->count())
            : 0;

        return [
            'visible_projects_count' => $projects->count(),
            'active_projects_count' => $activeCount,
            'closed_projects_count' => $closedCount,
            'projects_with_open_tasks_count' => $withOpenTasksCount,
            'projects_with_overdue_tasks_count' => $withOverdueTasksCount,
            'projects_average_progress' => $averageProgress,
        ];
    }
}
