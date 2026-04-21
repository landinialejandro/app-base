<?php

// FILE: app/Support/Appointments/AppointmentSurfaceService.php | V7

namespace App\Support\Appointments;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Support\Collection;

class AppointmentSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'appointments.related',
                label: 'Turnos',
                targets: ['assets.show', 'parties.show'],
                slot: 'tab_panels',
                priority: 40,
                view: 'appointments.partials.embedded-tabs',
                resolver: $this->resolveRelated(...),
            ),
            $this->linkedOffer(
                key: 'appointment.linked',
                label: 'Turno',
                targets: ['orders.show'],
                slot: 'detail_items',
                priority: 20,
                view: 'appointments.components.linked-appointment',
                resolver: $this->resolveLinkedForOrder(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'appointments.show' && $record instanceof Appointment) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'appointment',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'assets.show' && $record instanceof Asset) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'asset',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'parties.show' && $record instanceof Party) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'party',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'orders.show' && $record instanceof Order) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'order',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        return [];
    }

    private function resolveRelated(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if (! in_array($recordType, ['asset', 'party'], true)) {
            return $this->emptyRelatedPayload($trailQuery);
        }

        $appointments = match ($recordType) {
            'asset' => $record instanceof Asset
                ? $this->appointmentsForAsset($record)
                : collect(),
            'party' => $record instanceof Party
                ? $this->appointmentsForParty($record)
                : collect(),
            default => collect(),
        };

        return [
            'count' => $appointments->count(),
            'data' => [
                'appointments' => $appointments,
                'recordType' => $recordType,
                'tabsId' => $recordType.'-appointments-tabs',
                'trailQuery' => $trailQuery,
                'createBaseQuery' => $this->createBaseQuery($record, $recordType),
            ],
        ];
    }

    private function resolveLinkedForOrder(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'data' => [
                    'linked' => null,
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'linked' => AppointmentLinked::forOrder($record, $trailQuery),
                'variant' => 'summary',
            ],
        ];
    }

    private function emptyRelatedPayload(array $trailQuery): array
    {
        return [
            'count' => 0,
            'data' => [
                'appointments' => collect(),
                'recordType' => null,
                'tabsId' => 'appointments-tabs-empty',
                'trailQuery' => $trailQuery,
                'createBaseQuery' => [],
            ],
        ];
    }

    private function createBaseQuery(mixed $record, ?string $recordType): array
    {
        return match ($recordType) {
            'asset' => $record instanceof Asset ? array_filter([
                'asset_id' => $record->getKey(),
                'party_id' => $record->party_id,
            ], fn ($value) => $value !== null) : [],

            'party' => $record instanceof Party ? [
                'party_id' => $record->getKey(),
            ] : [],

            default => [],
        };
    }

    private function appointmentsForAsset(Asset $asset): Collection
    {
        return app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->with(['party', 'asset'])
            ->where('asset_id', $asset->getKey())
            ->orderByDesc('scheduled_date')
            ->orderByDesc('starts_at')
            ->get();
    }

    private function appointmentsForParty(Party $party): Collection
    {
        return app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->with(['party', 'asset'])
            ->where('party_id', $party->getKey())
            ->orderByDesc('scheduled_date')
            ->orderByDesc('starts_at')
            ->get();
    }

    public static function canCreate(): bool
    {
        return app(TenantModuleAccess::class)->isEnabled(ModuleCatalog::APPOINTMENTS)
            && auth()->check()
            && auth()->user()->can('create', Appointment::class);
    }
}
