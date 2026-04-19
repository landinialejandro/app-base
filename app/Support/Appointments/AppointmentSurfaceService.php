<?php

// FILE: app/Support/Appointments/AppointmentSurfaceService.php | V8

namespace App\Support\Appointments;

use App\Models\Appointment;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AppointmentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            $this->embeddedRelatedOffer(
                key: 'appointments.related',
                targets: ['assets.show', 'parties.show'],
                priority: 40,
            ),
            $this->linkedOffer(
                key: 'appointment.linked',
                label: 'Turno',
                targets: ['orders.show'],
                slot: 'detail_items',
                priority: 40,
                view: 'appointments.components.linked-appointment-action',
                resolver: $this->resolveLinkedForOrder(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return match ($host) {
            'appointments.show' => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'appointment',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            default => [],
        };
    }

    private function embeddedRelatedOffer(
        string $key,
        array $targets,
        int $priority,
    ): array {
        return [
            'type' => 'embedded',
            'key' => $key,
            'label' => 'Turnos',
            'targets' => $targets,
            'slot' => 'tab_panels',
            'priority' => $priority,
            'view' => 'appointments.partials.embedded-tabs',
            'needs' => ['record', 'recordType', 'trailQuery'],
            'resolver' => $this->resolveRelated(...),
        ];
    }

    private function linkedOffer(
        string $key,
        string $label,
        array $targets,
        string $slot,
        int $priority,
        string $view,
        callable $resolver,
    ): array {
        return [
            'type' => 'linked',
            'key' => $key,
            'label' => $label,
            'targets' => $targets,
            'slot' => $slot,
            'priority' => $priority,
            'view' => $view,
            'needs' => ['record', 'recordType', 'trailQuery'],
            'resolver' => $resolver,
        ];
    }

    /**
     * @param  array<string, mixed>  $hostPack
     * @return array<string, mixed>
     */
    protected function resolveRelated(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if (! $record instanceof Model || ! is_string($recordType)) {
            return $this->emptyRelatedPayload($trailQuery);
        }

        $appointments = $this->relatedAppointmentsFor($record, $recordType);
        $createBaseQuery = $this->createBaseQueryFor($record, $recordType);

        return [
            'count' => $appointments->count(),
            'data' => [
                'appointments' => $appointments,
                'supportsPartiesModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::PARTIES),
                'supportsAssetsModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ASSETS),
                'supportsOrdersModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ORDERS),
                'emptyMessage' => 'No hay turnos relacionados.',
                'tabsId' => 'appointments-related-'.$recordType.'-'.$record->getKey(),
                'createBaseQuery' => $createBaseQuery,
                'trailQuery' => $trailQuery,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $hostPack
     * @return array<string, mixed>
     */
    protected function resolveLinkedForOrder(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'data' => [
                    'action' => null,
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => AppointmentLinkedAction::forOrder($record, $trailQuery),
                'variant' => 'summary',
            ],
        ];
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function relatedAppointmentsFor(Model $record, string $recordType): Collection
    {
        return app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->when($recordType === 'asset', fn ($query) => $query->where('asset_id', $record->getKey()))
            ->when($recordType === 'party', fn ($query) => $query->where('party_id', $record->getKey()))
            ->orderByDesc('scheduled_date')
            ->orderByDesc('starts_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function createBaseQueryFor(Model $record, string $recordType): array
    {
        return match ($recordType) {
            'asset' => array_filter([
                'asset_id' => $record->getKey(),
                'party_id' => $record->party_id ?? null,
            ], fn ($value) => $value !== null),
            'party' => [
                'party_id' => $record->getKey(),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRelatedPayload(array $trailQuery): array
    {
        return [
            'count' => 0,
            'data' => [
                'appointments' => collect(),
                'supportsPartiesModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::PARTIES),
                'supportsAssetsModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ASSETS),
                'supportsOrdersModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ORDERS),
                'emptyMessage' => 'No hay turnos relacionados.',
                'tabsId' => 'appointments-related-empty',
                'createBaseQuery' => [],
                'trailQuery' => $trailQuery,
            ],
        ];
    }
}
