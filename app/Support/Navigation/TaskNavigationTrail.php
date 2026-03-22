<?php

// FILE: app/Support/Navigation/TaskNavigationTrail.php | V1

namespace App\Support\Navigation;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskNavigationTrail
{
    public static function tasksBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('tasks.index', null, 'Tareas', route('tasks.index')),
        ]);
    }

    public static function base(Task $task): array
    {
        $trail = $task->project
            ? ProjectNavigationTrail::base($task->project)
            : self::tasksBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'tasks.show',
                $task->id,
                $task->name ?: 'Tarea #'.$task->id,
                route('tasks.show', ['task' => $task])
            )
        );
    }

    public static function create(Request $request, ?Project $project = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = $project
                ? ProjectNavigationTrail::base($project)
                : self::tasksBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'tasks.create',
                'new',
                'Nueva tarea',
                route('tasks.create')
            )
        );
    }

    public static function show(Request $request, Task $task): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            return self::base($task);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'tasks.show',
                $task->id,
                $task->name ?: 'Tarea #'.$task->id,
                route('tasks.show', ['task' => $task])
            )
        );
    }

    public static function edit(Request $request, Task $task): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'tasks.show', $task->id)) {
            $trail = self::show($request, $task);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'tasks.edit',
                $task->id,
                'Editar',
                route('tasks.edit', ['task' => $task])
            )
        );
    }
}
