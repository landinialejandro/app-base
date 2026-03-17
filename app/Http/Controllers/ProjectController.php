<?php

// FILE: app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->get('q', ''));

        $projects = Project::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->latest()
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

        return view('projects.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::create($data);

        return redirect()
            ->route('projects.index')
            ->with('success', "Proyecto #{$project->id} creado correctamente.");
    }

    public function show(Project $project)
    {
        $tenant = app('tenant');

        $project->load([
            'tasks' => function ($query) {
                $query->with('assignedUser')
                    ->orderBy('due_date')
                    ->orderBy('name');
            },
        ]);

        return view('projects.show', [
            'tenant' => $tenant,
            'project' => $project,
        ]);
    }

    public function edit(Project $project)
    {
        $tenant = app('tenant');

        return view('projects.edit', [
            'tenant' => $tenant,
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyecto actualizado');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Proyecto eliminado correctamente.');
    }
}
