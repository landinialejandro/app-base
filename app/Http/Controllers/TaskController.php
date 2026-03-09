<?php

// file:app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        $tasks = Task::query()
            ->with(['project', 'party', 'assignedUser'])
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();

        return view('tasks.index', compact('tenant', 'tasks'));
    }

    public function create()
    {
        $tenant = app('tenant');

        $projects = Project::query()
            ->orderBy('name')
            ->get();

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->orderBy('name')
            ->get();

        return view('tasks.create', compact('tenant', 'projects', 'parties', 'users'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'done', 'cancelled'])],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['tenant_id'] = $tenant->id;

        Task::create($data);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tarea creada correctamente');
    }

    public function show(Task $task)
    {
        $tenant = app('tenant');

        $task->load(['project', 'party', 'assignedUser']);

        return view('tasks.show', compact('tenant', 'task'));
    }

    public function edit(Task $task)
    {
        $tenant = app('tenant');

        $projects = Project::query()
            ->orderBy('name')
            ->get();

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->orderBy('name')
            ->get();

        return view('tasks.edit', compact('tenant', 'task', 'projects', 'parties', 'users'));
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'party_id' => ['nullable', 'integer', 'exists:parties,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'done', 'cancelled'])],
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update($data);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Tarea actualizada correctamente');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tarea eliminada correctamente');
    }
}