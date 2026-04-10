<?php

// FILE: app/Http/Controllers/TaskController.php | V11

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Navigation\NavigationTrail;
use App\Support\Navigation\TaskNavigationTrail;
use App\Support\Tasks\TaskVisibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $security = app(Security::class);
        $user = auth()->user();

        $this->authorize('viewAny', Task::class);

        $q = trim((string) $request->get('q', ''));
        $projectId = $request->get('project_id');
        $status = $request->get('status');
        $priority = $request->get('priority');
        $assignedUserId = $request->get('assigned_user_id');

        $effectiveScope = $this->taskViewScope();
        $canViewAll = $effectiveScope === PermissionScopeCatalog::TENANT_ALL;

        $projects = $security
            ->scope($user, 'projects.viewAny', Project::query())
            ->orderBy('name')
            ->get();

        $users = $security
            ->scope($user, 'users.viewAny', User::query())
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $baseQuery = match ($effectiveScope) {
            PermissionScopeCatalog::TENANT_ALL => Task::query(),
            PermissionScopeCatalog::LIMITED => TaskVisibility::visibleQuery(),
            default => Task::query()->whereRaw('1 = 0'),
        };

        $tasks = $baseQuery
            ->with(['project', 'party', 'assignedUser', 'order'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($priority, fn ($query) => $query->where('priority', $priority))
            ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
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

        return view('tasks.index', [
            'tenant' => $tenant,
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'scope' => $effectiveScope,
            'canViewAll' => $canViewAll,
        ]);
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
        $navigationTrail = TaskNavigationTrail::create($request, $forcedProject);

        return view('tasks.create', compact(
            'tenant',
            'projects',
            'parties',
            'users',
            'forcedProject',
            'defaultAssignedUserId',
            'navigationTrail'
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
        $task->load(['project', 'party', 'assignedUser', 'order']);

        $navigationTrail = TaskNavigationTrail::show($request, $task);

        return redirect()
            ->route('tasks.show', ['task' => $task] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Tarea creada correctamente.');
    }

    public function show(Request $request, Task $task)
    {
        $tenant = app('tenant');

        $this->authorize('view', $task);

        $task->load([
            'project',
            'party',
            'assignedUser',
            'order',
            'attachments' => fn ($query) => $query->ordered(),
        ]);

        $canEditTask = auth()->user()->can('update', $task);
        $canDeleteTask = auth()->user()->can('delete', $task);
        $isForeignTaskForAdmin = $this->canManageForeignTask($task);

        $navigationTrail = TaskNavigationTrail::show($request, $task);

        return view('tasks.show', compact(
            'tenant',
            'task',
            'navigationTrail',
            'canEditTask',
            'canDeleteTask',
            'isForeignTaskForAdmin'
        ));
    }

    public function edit(Request $request, Task $task)
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
        $defaultAssignedUserId = old('assigned_user_id', (string) ($task->assigned_user_id ?? auth()->id()));
        $isForeignTaskForAdmin = $this->canManageForeignTask($task);

        $navigationTrail = TaskNavigationTrail::edit($request, $task);

        return view('tasks.edit', compact(
            'tenant',
            'task',
            'projects',
            'parties',
            'users',
            'forcedProject',
            'defaultAssignedUserId',
            'isForeignTaskForAdmin',
            'navigationTrail'
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

        $isAdminEditingForeignTask = $this->canManageForeignTask($task);

        if ($isAdminEditingForeignTask && $request->input('confirm_foreign_task_edit') !== '1') {
            return back()
                ->withErrors([
                    'confirm_foreign_task_edit' => 'Estás editando una tarea asignada a otro colaborador. Confirmá la modificación antes de guardar.',
                ])
                ->withInput();
        }

        unset($data['confirm_foreign_task_edit']);

        $task->update($data);
        $task->load(['project', 'party', 'assignedUser', 'order']);

        $navigationTrail = TaskNavigationTrail::show($request, $task);

        return redirect()
            ->route('tasks.show', ['task' => $task] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Tarea actualizada correctamente.');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->load('project');

        $navigationTrail = TaskNavigationTrail::show($request, $task);
        $redirectUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            $task->project
                ? route('projects.show', $task->project)
                : route('tasks.index')
        );

        $task->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Tarea eliminada correctamente.');
    }

    protected function taskViewScope(): mixed
    {
        $inspection = app(Security::class)->inspect(auth()->user(), 'tasks.viewAny');

        return $inspection['scope'] ?? false;
    }

    protected function canManageForeignTask(Task $task): bool
    {
        $inspection = app(Security::class)->inspect(auth()->user(), 'tasks.update', $task);

        return in_array(
            $inspection['scope'] ?? null,
            [
                PermissionScopeCatalog::TENANT_ALL,
                PermissionScopeCatalog::LIMITED,
            ],
            true
        ) && (int) $task->assigned_user_id !== (int) auth()->id();
    }
}
