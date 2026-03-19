<?php

// FILE: app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\Catalogs\ProjectCatalog;
use App\Support\Catalogs\TaskCatalog;
use App\Support\Projects\ProjectMetrics;
use App\Support\Projects\ProjectVisibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('viewAny', Project::class);

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');

        $projects = ProjectVisibility::visibleQuery()
            ->select('projects.*')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->whereIn('tasks.status', [
                        TaskCatalog::STATUS_PENDING,
                        TaskCatalog::STATUS_IN_PROGRESS,
                    ]);
            }, 'open_tasks_count')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->where('tasks.status', TaskCatalog::STATUS_IN_PROGRESS);
            }, 'in_progress_tasks_count')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->whereNotIn('tasks.status', [
                        TaskCatalog::STATUS_DONE,
                        TaskCatalog::STATUS_CANCELLED,
                    ])
                    ->whereDate('tasks.due_date', '<', now()->toDateString());
            }, 'overdue_tasks_count')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at');
            }, 'tasks_count')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->where('tasks.status', TaskCatalog::STATUS_DONE);
            }, 'done_tasks_count')
            ->selectSub(function ($query) {
                $query->from('tasks')
                    ->selectRaw('MIN(tasks.due_date)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->whereNull('tasks.deleted_at')
                    ->whereNotIn('tasks.status', [
                        TaskCatalog::STATUS_DONE,
                        TaskCatalog::STATUS_CANCELLED,
                    ])
                    ->whereNotNull('tasks.due_date');
            }, 'next_due_date')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when(
                $status !== '' && in_array($status, ProjectCatalog::statuses(), true),
                function ($query) use ($status) {
                    $query->where('status', $status);
                }
            )
            ->orderByRaw('
                CASE
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    ELSE 3
                END
            ', [
                ProjectCatalog::STATUS_ACTIVE,
                ProjectCatalog::STATUS_CLOSED,
            ])
            ->orderByRaw('CASE WHEN next_due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_due_date')
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', [
            'tenant' => $tenant,
            'projects' => $projects,
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');

        $this->authorize('create', Project::class);

        return view('projects.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(ProjectCatalog::statuses())],
        ]);

        $project = Project::create($data);

        return redirect()
            ->route('projects.index')
            ->with('success', "Proyecto #{$project->id} creado correctamente.");
    }

    public function show(Project $project)
    {
        $tenant = app('tenant');

        $this->authorize('view', $project);

        $project->load([
            'tasks' => function ($query) {
                $query->with(['assignedUser', 'order'])
                    ->orderBy('due_date')
                    ->orderBy('name');
            },
        ]);

        $metrics = ProjectMetrics::forShow($project);

        return view('projects.show', [
            'tenant' => $tenant,
            'project' => $project,
            'metrics' => $metrics,
        ]);
    }

    public function edit(Project $project)
    {
        $tenant = app('tenant');

        $this->authorize('update', $project);

        return view('projects.edit', [
            'tenant' => $tenant,
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(ProjectCatalog::statuses())],
        ]);

        $project->update($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyecto actualizado');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Proyecto eliminado correctamente.');
    }
}
