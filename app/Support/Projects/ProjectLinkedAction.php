<?php

// FILE: app/Support/Projects/ProjectLinkedAction.php | V1

namespace App\Support\Projects;

use App\Models\Project;

class ProjectLinkedAction
{
    public static function forProject(?Project $project, array $trailQuery = [], string $label = 'Proyecto'): array
    {
        if (! $project) {
            return [
                'supported' => false,
                'linked' => false,
                'can_view' => false,
                'hidden' => true,
                'show_url' => null,
                'label' => $label,
                'linked_text' => $label,
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $project));

        return [
            'supported' => true,
            'linked' => true,
            'can_view' => $canView,
            'hidden' => false,
            'show_url' => $canView
                ? route('projects.show', ['project' => $project] + $trailQuery)
                : null,
            'label' => $label,
            'linked_text' => $project->name ?: 'Proyecto #'.$project->getKey(),
        ];
    }
}
