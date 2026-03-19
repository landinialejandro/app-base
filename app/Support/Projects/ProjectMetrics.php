<?php

namespace App\Support\Projects;

use App\Models\Project;

class ProjectMetrics
{
    public static function forShow(Project $project): array
    {
        $tasks = $project->tasks
            ->sortBy([
                ['due_date', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $openTasks = $tasks->whereIn('status', ['pending', 'in_progress'])->values();
        $doneTasks = $tasks->where('status', 'done')->values();
        $cancelledTasks = $tasks->where('status', 'cancelled')->values();

        $pendingCount = $tasks->where('status', 'pending')->count();
        $inProgressCount = $tasks->where('status', 'in_progress')->count();
        $doneCount = $doneTasks->count();
        $cancelledCount = $cancelledTasks->count();

        $overdueCount = $tasks
            ->filter(function ($task) {
                return $task->due_date &&
                    $task->due_date->isPast() &&
                    ! in_array($task->status, ['done', 'cancelled'], true);
            })
            ->count();

        $totalTasks = $tasks->count();
        $progress = $totalTasks > 0 ? round(($doneCount / $totalTasks) * 100) : 0;

        $projectStartDate = $project->created_at?->copy()->startOfDay();
        $today = now()->startOfDay();

        $daysElapsed = $projectStartDate
            ? $projectStartDate->diffInDays($today)
            : null;

        $lastOpenDueDate = $openTasks
            ->filter(fn ($task) => ! empty($task->due_date))
            ->sortByDesc(fn ($task) => $task->due_date)
            ->first()
            ?->due_date?->copy()
            ?->startOfDay();

        $daysRemaining = null;

        if ($lastOpenDueDate) {
            $daysRemaining = $today->diffInDays($lastOpenDueDate, false);
        }

        $pendingPercent = $totalTasks > 0 ? round(($pendingCount / $totalTasks) * 100, 2) : 0;
        $inProgressPercent = $totalTasks > 0 ? round(($inProgressCount / $totalTasks) * 100, 2) : 0;
        $donePercent = $totalTasks > 0 ? round(($doneCount / $totalTasks) * 100, 2) : 0;
        $cancelledPercent = $totalTasks > 0 ? round(($cancelledCount / $totalTasks) * 100, 2) : 0;

        $pieSegments = [];
        $offset = 0;

        foreach ([
            [
                'key' => 'pending',
                'count' => $pendingCount,
                'percent' => $pendingPercent,
                'class' => 'project-pie-segment--pending',
            ],
            [
                'key' => 'in_progress',
                'count' => $inProgressCount,
                'percent' => $inProgressPercent,
                'class' => 'project-pie-segment--in-progress',
            ],
            [
                'key' => 'done',
                'count' => $doneCount,
                'percent' => $donePercent,
                'class' => 'project-pie-segment--done',
            ],
            [
                'key' => 'cancelled',
                'count' => $cancelledCount,
                'percent' => $cancelledPercent,
                'class' => 'project-pie-segment--cancelled',
            ],
        ] as $segment) {
            if ($segment['count'] <= 0) {
                continue;
            }

            $pieSegments[] = [
                ...$segment,
                'dash' => $segment['percent'].' '.(100 - $segment['percent']),
                'offset' => -$offset,
            ];

            $offset += $segment['percent'];
        }

        return [
            'tasks' => $tasks,
            'openTasks' => $openTasks,
            'doneTasks' => $doneTasks,
            'cancelledTasks' => $cancelledTasks,

            'pendingCount' => $pendingCount,
            'inProgressCount' => $inProgressCount,
            'doneCount' => $doneCount,
            'cancelledCount' => $cancelledCount,
            'overdueCount' => $overdueCount,
            'totalTasks' => $totalTasks,
            'progress' => $progress,

            'projectStartDate' => $projectStartDate,
            'daysElapsed' => $daysElapsed,
            'lastOpenDueDate' => $lastOpenDueDate,
            'daysRemaining' => $daysRemaining,

            'pieSegments' => $pieSegments,
        ];
    }
}
