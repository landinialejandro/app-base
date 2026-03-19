<?php

// FILE: app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Tasks\TaskVisibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('viewAny', Task::class);

        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');
        $status = $request->get('status');
        $priority = $request->get('priority');
        $assignedUserId = $request->get('assigned_user_id');
        $scope = $request->get('scope', 'mine');

        if (! in_array($scope, ['mine', 'all'], true)) {
            $scope = 'mine';
        }

        $projects = Project::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $tasks = TaskVisibility::visibleQuery()
            ->with(['project', 'party', 'assignedUser', 'order'])
            ->when($scope === 'mine', function ($query) {
                $query->where('assigned_user_id', auth()->id());
            })
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
            ->when($priority, function ($query) use ($priority) {
                $query->where('priority', $priority);
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
            ->orderByRaw(
                'CASE
                    WHEN priority = ? THEN 1
                    WHEN priority = ? THEN 2
                    WHEN priority = ? THEN 3
                    WHEN priority = ? THEN 4
                    ELSE 5
                END',
                [
                    TaskCatalog::PRIORITY_URGENT,
                    TaskCatalog::PRIORITY_HIGH,
                    TaskCatalog::PRIORITY_MEDIUM,
                    TaskCatalog::PRIORITY_LOW,
                ]
            )
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('tasks.index', compact('tenant', 'tasks', 'projects', 'users', 'scope'));
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('create', Task::class);

        $forcedProject = null;

        if ($request->filled('project_id')) {
            $forcedProject = Project::query()
                ->whereKey($request->integer('project_id'))
                ->firstOrFail();
        }

        $projects = Project::query()->orderBy('name')->get();
        $parties = Party::query()->orderBy('name')->get();
        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $defaultAssignedUserId = old('assigned_user_id', (string) auth()->id());

        $breadcrumbItems = $forcedProject
            ? [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Proyectos', 'url' => route('projects.index')],
                ['label' => $forcedProject->name, 'url' => route('projects.show', $forcedProject)],
                ['label' => 'Nueva tarea'],
            ]
            : [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Tareas', 'url' => route('tasks.index')],
                ['label' => 'Nueva tarea'],
            ];

        $canChangeProject = auth()->user()->can('create', Project::class);

        return view('tasks.create', compact(
            'tenant',
            'projects',
            'parties',
            'users',
            'forcedProject',
            'breadcrumbItems',
            'defaultAssignedUserId',
            'canChangeProject'
        ));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('create', Task::class);

        $data = $request->validate([
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'assigned_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(TaskCatalog::statuses())],
            'priority' => ['required', 'string', Rule::in(array_keys(TaskCatalog::priorityLabels()))],
            'due_date' => ['nullable', 'date'],
        ]);

        $userBelongsToTenant = User::query()
            ->whereKey($data['assigned_user_id'])
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->exists();

        if (! $userBelongsToTenant) {
            return back()
                ->withErrors([
                    'assigned_user_id' => 'El colaborador asignado no pertenece a la empresa actual.',
                ])
                ->withInput();
        }

        $task = Task::create($data);
        $task->load('project');

        if ($task->project) {
            return redirect()
                ->route('projects.show', $task->project)
                ->with('success', 'Tarea creada correctamente.');
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Tarea creada correctamente.');
    }

    public function show(Task $task)
    {
        $tenant = app('tenant');

        $this->authorize('view', $task);

        $task->load(['project', 'party', 'assignedUser', 'order']);

        $canEditTask = auth()->user()->can('update', $task);
        $canDeleteTask = auth()->user()->can('delete', $task);
        $isForeignTaskForAdmin = $canDeleteTask
            && (int) $task->assigned_user_id !== (int) auth()->id();

        $breadcrumbItems = $task->project
            ? [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Proyectos', 'url' => route('projects.index')],
                ['label' => $task->project->name, 'url' => route('projects.show', $task->project)],
                ['label' => $task->name],
            ]
            : [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Tareas', 'url' => route('tasks.index')],
                ['label' => $task->name],
            ];

        return view('tasks.show', compact(
            'tenant',
            'task',
            'breadcrumbItems',
            'canEditTask',
            'canDeleteTask',
            'isForeignTaskForAdmin'
        ));
    }

    public function edit(Task $task)
    {
        $tenant = app('tenant');

        $this->authorize('update', $task);

        $task->load(['project', 'order']);

        $projects = Project::query()->orderBy('name')->get();
        $parties = Party::query()->orderBy('name')->get();
        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $forcedProject = null;
        $canChangeProject = auth()->user()->can('create', Project::class);
        $defaultAssignedUserId = old('assigned_user_id', (string) ($task->assigned_user_id ?? auth()->id()));
        $isForeignTaskForAdmin = auth()->user()->can('delete', $task)
            && (int) $task->assigned_user_id !== (int) auth()->id();

        $breadcrumbItems = $task->project
            ? [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Proyectos', 'url' => route('projects.index')],
                ['label' => $task->project->name, 'url' => route('projects.show', $task->project)],
                ['label' => $task->name, 'url' => route('tasks.show', $task)],
                ['label' => 'Editar'],
            ]
            : [
                ['label' => 'Inicio', 'url' => route('dashboard')],
                ['label' => 'Tareas', 'url' => route('tasks.index')],
                ['label' => $task->name, 'url' => route('tasks.show', $task)],
                ['label' => 'Editar'],
            ];

        return view('tasks.edit', compact(
            'tenant',
            'task',
            'projects',
            'parties',
            'users',
            'forcedProject',
            'breadcrumbItems',
            'canChangeProject',
            'defaultAssignedUserId',
            'isForeignTaskForAdmin'
        ));
    }

    public function update(Request $request, Task $task)
    {
        $tenant = app('tenant');

        $this->authorize('update', $task);

        $data = $request->validate([
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'assigned_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(TaskCatalog::statuses())],
            'priority' => ['required', 'string', Rule::in(array_keys(TaskCatalog::priorityLabels()))],
            'due_date' => ['nullable', 'date'],
            'confirm_foreign_task_edit' => ['nullable', 'string'],
        ]);

        $userBelongsToTenant = User::query()
            ->whereKey($data['assigned_user_id'])
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->exists();

        if (! $userBelongsToTenant) {
            return back()
                ->withErrors([
                    'assigned_user_id' => 'El colaborador asignado no pertenece a la empresa actual.',
                ])
                ->withInput();
        }

        $isAdminEditingForeignTask = auth()->user()->can('delete', $task)
            && (int) $task->assigned_user_id !== (int) auth()->id();

        if ($isAdminEditingForeignTask && $request->input('confirm_foreign_task_edit') !== '1') {
            return back()
                ->withErrors([
                    'confirm_foreign_task_edit' => 'Estás editando una tarea asignada a otro colaborador. Confirmá la modificación antes de guardar.',
                ])
                ->withInput();
        }

        if (! auth()->user()->can('create', Project::class)) {
            $data['project_id'] = $task->project_id;
        }

        unset($data['confirm_foreign_task_edit']);

        $task->update($data);
        $task->load('project');

        if ($task->project) {
            return redirect()
                ->route('projects.show', $task->project)
                ->with('success', 'Tarea actualizada correctamente.');
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Tarea actualizada correctamente.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->load('project');
        $project = $task->project;

        $task->delete();

        if ($project) {
            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'Tarea eliminada correctamente.');
        }

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tarea eliminada correctamente.');
    }
}
