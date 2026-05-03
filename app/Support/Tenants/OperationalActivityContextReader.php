<?php

// FILE: app/Support/Tenants/OperationalActivityContextReader.php | V1

namespace App\Support\Tenants;

use App\Models\OperationalActivity;
use App\Support\Navigation\NavigationTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OperationalActivityContextReader
{
    public function __construct(
        protected OperationalActivityLinkResolver $linkResolver
    ) {
    }

    /**
     * Lee actividad operativa asociada a un registro host ya autorizado.
     *
     * Esta pieza no autoriza el acceso al registro host.
     * El controller o flujo consumidor debe haber ejecutado previamente
     * la autorización propia del módulo dueño.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
public function forRecord(Model $record, array $trail = [], int $limit = 20): Collection
{
    return $this->forRecordSet([$record], $record, $trail, $limit);
}

    protected function trailForRecord(Model $record, array $trail): array
    {
        if (! empty($trail)) {
            return $trail;
        }

        return NavigationTrail::base([
            NavigationTrail::makeNode(
                'dashboard',
                'dashboard',
                'Inicio',
                route('dashboard')
            ),
        ]);
    }


public function forRecordSet(iterable $records, Model $trailRecord, array $trail = [], int $limit = 20): Collection
{
    $tenant = app('tenant');

    $safeLimit = max(1, min($limit, 100));

    $activityTrail = $this->trailForRecord($trailRecord, $trail);
    $changePresenter = app(OperationalActivityChangePresenter::class);

    $recordGroups = collect($records)
        ->filter(fn ($record) => $record instanceof Model)
        ->filter(fn (Model $record) => $record->getKey() !== null)
        ->unique(fn (Model $record) => $record->getMorphClass().':'.$record->getKey())
        ->groupBy(fn (Model $record) => $record->getMorphClass())
        ->map(fn (Collection $group) => $group
            ->map(fn (Model $record) => $record->getKey())
            ->values()
            ->all()
        )
        ->filter(fn (array $ids) => $ids !== []);

    if ($recordGroups->isEmpty()) {
        return collect();
    }

    return OperationalActivity::query()
        ->where('tenant_id', $tenant->id)
        ->where(function ($query) use ($recordGroups) {
            foreach ($recordGroups as $recordType => $recordIds) {
                $query->orWhere(function ($subquery) use ($recordType, $recordIds) {
                    $subquery
                        ->where('record_type', $recordType)
                        ->whereIn('record_id', $recordIds);
                });
            }
        })
        ->with(['actorUser', 'subjectUser', 'record'])
        ->orderByDesc('occurred_at')
        ->orderByDesc('id')
        ->limit($safeLimit)
        ->get()
        ->map(function (OperationalActivity $activity) use ($activityTrail, $changePresenter) {
            $recordLink = $this->linkResolver->resolve($activity, $activityTrail);
            $metadata = $activity->metadata ?? [];

            return [
                'id' => $activity->id,
                'occurred_at' => $activity->occurred_at,
                'module' => $activity->module,
                'activity_type' => $activity->activity_type,
                'record_label' => $recordLink['label'],
                'record_url' => $recordLink['url'],
                'actor_label' => $activity->actorUser?->name
                    ?? $activity->actorUser?->email
                    ?? 'Sistema',
                'subject_label' => $activity->subjectUser?->name
                    ?? $activity->subjectUser?->email
                    ?? '—',
                'metadata' => $metadata,
                'change_summary' => $changePresenter->summary($metadata),
                'change_details' => $changePresenter->details($metadata)->all(),
            ];
        });
}
}