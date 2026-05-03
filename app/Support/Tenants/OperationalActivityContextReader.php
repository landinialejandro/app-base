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
        $tenant = app('tenant');

        $safeLimit = max(1, min($limit, 100));

        $activityTrail = $this->trailForRecord($record, $trail);

        return OperationalActivity::query()
            ->where('tenant_id', $tenant->id)
            ->where('record_type', $record->getMorphClass())
            ->where('record_id', $record->getKey())
            ->with(['actorUser', 'subjectUser', 'record'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($safeLimit)
            ->get()
            ->map(function (OperationalActivity $activity) use ($activityTrail) {
                $recordLink = $this->linkResolver->resolve($activity, $activityTrail);

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
                    'metadata' => $activity->metadata ?? [],
                ];
            });
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
}