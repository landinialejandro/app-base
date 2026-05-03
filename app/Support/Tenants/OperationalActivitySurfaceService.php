<?php

// FILE: app/Support/Tenants/OperationalActivitySurfaceService.php | V1

namespace App\Support\Tenants;

use Illuminate\Database\Eloquent\Model;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class OperationalActivitySurfaceService implements ModuleSurfaceService
{
public function offers(): array
{
    return [
        [
            'type' => 'embedded',
            'key' => 'activity',
            'label' => 'Actividad',
            'targets' => [
                'tasks.show',
                'parties.show',
            ],
            'slot' => 'tab_panels',
            'priority' => 95,
            'view' => 'tenants.partials.operational-activity-table',
            'needs' => [
                'record',
            ],
            'resolver' => fn (array $hostPack) => $this->resolveContextualActivity($hostPack),
        ],
    ];
}

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveContextualActivity(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;

        if (! $record instanceof Model) {
            return $this->emptyPayload();
        }

        $trail = is_array($hostPack['navigationTrail'] ?? null)
            ? $hostPack['navigationTrail']
            : [];

        $rows = app(OperationalActivityContextReader::class)
            ->forRecord($record, $trail, 20);

        return [
            'count' => $rows->count(),
            'data' => [
                'operationalActivityRows' => $rows,
                'title' => 'Actividad operativa',
                'description' => 'Registro reciente de actividad operativa asociada a este recurso.',
                'emptyLabel' => 'Sin actividad registrada',
                'emptyMessage' => 'Todavía no hay actividad operativa registrada para este recurso.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyPayload(): array
    {
        return [
            'count' => 0,
            'data' => [
                'operationalActivityRows' => collect(),
                'title' => 'Actividad operativa',
                'description' => 'Registro reciente de actividad operativa asociada a este recurso.',
                'emptyLabel' => 'Sin actividad registrada',
                'emptyMessage' => 'Todavía no hay actividad operativa registrada para este recurso.',
            ],
        ];
    }
}