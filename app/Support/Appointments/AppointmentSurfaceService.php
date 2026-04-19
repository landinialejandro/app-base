<?php

// FILE: app/Support/Appointments/AppointmentSurfaceService.php | V6

namespace App\Support\Appointments;

use App\Models\Appointment;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Support\Collection;

class AppointmentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'embedded',
                'key' => 'appointments.related',
                'label' => 'Turnos',
                'targets' => ['assets.show', 'parties.show'],
                'slot' => 'tab_panels',
                'priority' => 40,
                'view' => 'appointments.partials.embedded-tabs',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveRelated(...),
            ],
            [
                'type' => 'linked',
                'key' => 'appointment.linked',
                'label' => 'Turno',
                'targets' => ['orders.show'],
                'slot' => 'detail_items',
                'priority' => 40,
                'view' => 'appointments.components.linked-appointment-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForOrder(...),
            ],
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return match ($host) {
            'appointments.show' => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'appointment',
                'trailQuery' => $context['trailQuery'] ?? [],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $hostPack
     * @return array<string, mixed>
     */
    protected function resolveRelated(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = $hostPack['trailQuery'] ?? [];

        if (! $record || ! is_string($recordType)) {
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

        /** @var Collection<int, Appointment> $appointments */
        $appointments = app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->when($recordType === 'asset', fn ($query) => $query->where('asset_id', $record->getKey()))
            ->when($recordType === 'party', fn ($query) => $query->where('party_id', $record->getKey()))
            ->orderByDesc('scheduled_at')
            ->get();

        $createBaseQuery = match ($recordType) {
            'asset' => [
                'asset_id' => $record->getKey(),
                'party_id' => $record->party_id ?? null,
            ],
            'party' => [
                'party_id' => $record->getKey(),
            ],
            default => [],
        };

        return [
            'count' => $appointments->count(),
            'data' => [
                'appointments' => $appointments,
                'supportsPartiesModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::PARTIES),
                'supportsAssetsModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ASSETS),
                'supportsOrdersModule' => app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::ORDERS),
                'emptyMessage' => 'No hay turnos relacionados.',
                'tabsId' => 'appointments-related-'.$recordType.'-'.$record->getKey(),
                'createBaseQuery' => array_filter($createBaseQuery, fn ($value) => $value !== null),
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
        $trailQuery = $hostPack['trailQuery'] ?? [];

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
}
