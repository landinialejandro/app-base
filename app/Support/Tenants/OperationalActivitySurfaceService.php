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
                'projects.show',
                'assets.show',
                'products.show',
                'appointments.show',
                'orders.show',
                'documents.show',
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

    $records = $this->activityRecordsFor($record);

    $rows = app(OperationalActivityContextReader::class)
        ->forRecordSet($records, $record, $trail, 20);

    $isAggregated = count($records) > 1;

    return [
        'count' => $rows->count(),
        'data' => [
            'operationalActivityRows' => $rows,
            'title' => $isAggregated ? 'Actividad relacionada' : 'Actividad operativa',
            'description' => $isAggregated
                ? 'Registro reciente de actividad operativa asociada a este recurso y sus registros relacionados.'
                : 'Registro reciente de actividad operativa asociada a este recurso.',
            'emptyLabel' => 'Sin actividad registrada',
            'emptyMessage' => $isAggregated
                ? 'Todavía no hay actividad operativa registrada para este recurso ni para sus registros relacionados.'
                : 'Todavía no hay actividad operativa registrada para este recurso.',
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


    protected function activityRecordsFor(Model $record): array
    {
        $module = OperationalActivityCatalog::moduleForRecordClass($record::class);
    
        if ($module === null) {
            return [$record];
        }
    
        $providerClass = \App\Support\Catalogs\ModuleCatalog::activityRecordSetService($module);
    
        if ($providerClass === null || ! class_exists($providerClass)) {
            return [$record];
        }
    
        $provider = app($providerClass);
    
        if (! $provider instanceof \App\Support\Modules\Contracts\ActivityRecordSetProvider) {
            return [$record];
        }
    
        return $provider
            ->forRecord($record)
            ->filter(fn ($item) => $item instanceof Model)
            ->values()
            ->all();
    }
}