<?php

// FILE: app/Support/Navigation/ProjectNavigationTrail.php | V3

namespace App\Support\Navigation;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectNavigationTrail
{
    public static function projectsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('projects.index', null, 'Proyectos', route('projects.index')),
        ]);
    }

    public static function base(Project $project): array
    {
        $trail = self::projectsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'projects.show',
                $project->id,
                $project->name ?: 'Proyecto #'.$project->id,
                route('projects.show', ['project' => $project])
            )
        );
    }

    public static function create(Request $request): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::projectsBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'projects.create',
                'new',
                'Nuevo proyecto',
                route('projects.create')
            )
        );
    }

    public static function show(Request $request, Project $project): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::base($project);
        }

        $trail = NavigationTrail::removeNodes($trail, [
            ['key' => 'projects.create', 'id' => 'new'],
            ['key' => 'projects.edit', 'id' => $project->id],
        ]);

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'projects.show',
                $project->id,
                $project->name ?: 'Proyecto #'.$project->id,
                route('projects.show', ['project' => $project])
            )
        );
    }

    public static function edit(Request $request, Project $project): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'projects.show', $project->id)) {
            $trail = self::show($request, $project);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'projects.edit',
                $project->id,
                'Editar',
                route('projects.edit', ['project' => $project])
            )
        );
    }
}
