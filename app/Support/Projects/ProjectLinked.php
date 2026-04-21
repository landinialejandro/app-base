<?php

// FILE: app/Support/Projects/ProjectLinked.php | V1

namespace App\Support\Projects;

use App\Models\Project;

class ProjectLinked
{
    public static function forProject(?Project $project, array $trailQuery = [], string $label = 'Proyecto'): array
    {
        if (! $project) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'label' => $label,
                'text' => '—',
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $project));

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView
                ? route('projects.show', ['project' => $project] + $trailQuery)
                : null,
            'label' => $label,
            'text' => $project->name ?: 'Proyecto #'.$project->getKey(),
        ];
    }
}
