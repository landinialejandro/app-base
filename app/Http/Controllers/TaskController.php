<?php

// FILE: app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Catalogs\TaskCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');
        $status = $request->get('status');
        $assignedUserId = $request->get('assigned_user_id');

        $projects = Project::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->orderBy('name')
            ->get();

        $tasks = Task::query()
            ->with(['project', 'party', 'assignedUser'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($projectId, function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($assignedUserId, function ($query) use ($assignedUserId) {
                $query->where('assigned_user_id', $assignedUserId);
            })
            ->orderByRaw(
                'CASE
                WHEN status = ? THEN 1
                WHEN status = ? THEN 2
                WHEN status = ? THEN 3
                WHEN status = ? THEN 4
                ELSE 5
            END',
                [
                    TaskCatalog::STATUS_PENDING,
                    TaskCatalog::STATUS_IN_PROGRESS,
                    TaskCatalog::STATUS_DONE,
                    TaskCatalog::STATUS_CANCELLED,
                ]
            )
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('tasks.index', compact('tenant', 'tasks', 'projects', 'users'));
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');
        $forcedProject = null;
        if ($request->filled('project_id')) {
            $forcedProject = Project::query()->whereKey($request->integer('project_id'))->firstOrFail();
        }
        $projects = Project::query()->orderBy('name')->get();
        $parties = Party::query()->orderBy('name')->get();
        $users = User::query()->whereHas('memberships', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->orderBy('name')->get();
        $breadcrumbItems = $forcedProject ? [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Proyectos', 'url' => route('projects.index')], ['label' => $forcedProject->name, 'url' => route('projects.show', $forcedProject)], ['label' => 'Nueva tarea']] : [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tareas', 'url' => route('tasks.index')], ['label' => 'Nueva tarea']];

        return view('tasks.create', compact('tenant', 'projects', 'parties', 'users', 'forcedProject', 'breadcrumbItems'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');
        $data = $request->validate(['project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where(function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
        }), ], 'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
        }), ], 'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['required', 'string', Rule::in(TaskCatalog::statuses())], 'due_date' => ['nullable', 'date'], ]);
        if (! empty($data['assigned_user_id'])) {
            $userBelongsToTenant = User::query()->whereKey($data['assigned_user_id'])->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->exists();
            if (! $userBelongsToTenant) {
                return back()->withErrors(['assigned_user_id' => 'El usuario asignado no pertenece a la empresa actual.'])->withInput();
            }
        }
        $task = Task::create($data);
        $task->load('project');
        if ($task->project) {
            return redirect()->route('projects.show', $task->project)->with('success', 'Tarea creada correctamente');
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Tarea creada correctamente');
    }

    public function show(Task $task)
    {
        $tenant = app('tenant');
        $task->load(['project', 'party', 'assignedUser']);
        $breadcrumbItems = $task->project ? [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Proyectos', 'url' => route('projects.index')], ['label' => $task->project->name, 'url' => route('projects.show', $task->project)], ['label' => $task->name]] : [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tareas', 'url' => route('tasks.index')], ['label' => $task->name]];

        return view('tasks.show', compact('tenant', 'task', 'breadcrumbItems'));
    }

    public function edit(Task $task)
    {
        $tenant = app('tenant');
        $task->load(['project']);
        $projects = Project::query()->orderBy('name')->get();
        $parties = Party::query()->orderBy('name')->get();
        $users = User::query()->whereHas('memberships', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->orderBy('name')->get();
        $forcedProject = null;
        $breadcrumbItems = $task->project ? [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Proyectos', 'url' => route('projects.index')], ['label' => $task->project->name, 'url' => route('projects.show', $task->project)], ['label' => $task->name, 'url' => route('tasks.show', $task)], ['label' => 'Editar']] : [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tareas', 'url' => route('tasks.index')], ['label' => $task->name, 'url' => route('tasks.show', $task)], ['label' => 'Editar']];

        return view('tasks.edit', compact('tenant', 'task', 'projects', 'parties', 'users', 'forcedProject', 'breadcrumbItems'));
    }

    public function update(Request $request, Task $task)
    {
        $tenant = app('tenant');
        $data = $request->validate(['project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where(function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
        }), ], 'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
        }), ], 'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['required', 'string', Rule::in(TaskCatalog::statuses())], 'due_date' => ['nullable', 'date'], ]);
        if (! empty($data['assigned_user_id'])) {
            $userBelongsToTenant = User::query()->whereKey($data['assigned_user_id'])->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->exists();
            if (! $userBelongsToTenant) {
                return back()->withErrors(['assigned_user_id' => 'El usuario asignado no pertenece a la empresa actual.'])->withInput();
            }
        }
        $task->update($data);
        $task->load('project');
        if ($task->project) {
            return redirect()->route('projects.show', $task->project)->with('success', 'Tarea actualizada correctamente');
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Tarea actualizada correctamente');
    }

    public function destroy(Task $task)
    {
        $task->load('project');
        $project = $task->project;
        $task->delete();
        if ($project) {
            return redirect()->route('projects.show', $project)->with('success', 'Tarea eliminada correctamente');
        }

        return redirect()->route('tasks.index')->with('success', 'Tarea eliminada correctamente');
    }
}
