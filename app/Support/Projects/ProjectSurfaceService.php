<?php

// FILE: app/Support/Projects/ProjectSurfaceService.php | V6

namespace App\Support\Projects;

use App\Models\Project;
use App\Models\Task;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class ProjectSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'project.task.linked',
                label: 'Proyecto',
                targets: ['tasks.show'],
                slot: 'summary_items',
                priority: 15,
                view: 'projects.components.linked-project-action',
                resolver: $this->resolveLinkedForTask(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'projects.show' && $record instanceof Project) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'project',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        return [];
    }

    private function resolveLinkedForTask(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'task' || ! $record instanceof Task) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'hidden' => true,
                        'show_url' => null,
                        'label' => 'Proyecto',
                        'linked_text' => 'Proyecto',
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => ProjectLinkedAction::forProject(
                    $record->project,
                    $trailQuery,
                    'Proyecto',
                ),
                'variant' => 'summary',
            ],
        ];
    }
}
