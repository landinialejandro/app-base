<?php

// FILE: app/Support/Parties/PartySurfaceService.php | V11

namespace App\Support\Parties;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class PartySurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'party.linked',
                label: AppointmentCatalog::contactLabel(),
                targets: ['appointments.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party',
                resolver: $this->resolveLinkedForAppointment(...),
            ),
            $this->linkedOffer(
                key: 'party.asset.linked',
                label: 'Contacto',
                targets: ['assets.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party',
                resolver: $this->resolveLinkedForAsset(...),
            ),
            $this->linkedOffer(
                key: 'party.order.linked',
                label: 'Contacto',
                targets: ['orders.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party',
                resolver: $this->resolveLinkedForOrder(...),
            ),
            $this->linkedOffer(
                key: 'party.task.linked',
                label: 'Contacto',
                targets: ['tasks.show'],
                slot: 'detail_items',
                priority: 20,
                view: 'parties.components.linked-party',
                resolver: $this->resolveLinkedForTask(...),
            ),
            $this->linkedOffer(
                key: 'party.document.linked',
                label: 'Contacto',
                targets: ['documents.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party',
                resolver: $this->resolveLinkedForDocument(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        $trailQuery = is_array($context['trailQuery'] ?? null)
            ? $context['trailQuery']
            : [];

        return match (true) {
            $host === 'parties.show' && $record instanceof Party => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'party',
                'trailQuery' => $trailQuery,
            ],

            $host === 'appointments.show' && $record instanceof Appointment => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'appointment',
                'trailQuery' => $trailQuery,
            ],

            $host === 'assets.show' && $record instanceof Asset => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'asset',
                'trailQuery' => $trailQuery,
            ],

            $host === 'orders.show' && $record instanceof Order => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'order',
                'trailQuery' => $trailQuery,
            ],

            $host === 'tasks.show' && $record instanceof Task => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'task',
                'trailQuery' => $trailQuery,
            ],

            $host === 'documents.show' && $record instanceof Document => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'document',
                'trailQuery' => $trailQuery,
            ],

            default => [],
        };
    }

    private function resolveLinkedForAppointment(array $hostPack): array
    {
        return $this->resolveLinked(
            hostPack: $hostPack,
            expectedRecordType: 'appointment',
            expectedClass: Appointment::class,
            label: AppointmentCatalog::contactLabel(),
            partyResolver: fn (Appointment $record) => $record->party,
        );
    }

    private function resolveLinkedForAsset(array $hostPack): array
    {
        return $this->resolveLinked(
            hostPack: $hostPack,
            expectedRecordType: 'asset',
            expectedClass: Asset::class,
            label: 'Contacto',
            partyResolver: fn (Asset $record) => $record->party,
        );
    }

    private function resolveLinkedForOrder(array $hostPack): array
    {
        return $this->resolveLinked(
            hostPack: $hostPack,
            expectedRecordType: 'order',
            expectedClass: Order::class,
            label: 'Contacto',
            partyResolver: fn (Order $record) => $record->party,
        );
    }

    private function resolveLinkedForTask(array $hostPack): array
    {
        return $this->resolveLinked(
            hostPack: $hostPack,
            expectedRecordType: 'task',
            expectedClass: Task::class,
            label: 'Contacto',
            partyResolver: fn (Task $record) => $record->party,
        );
    }

    private function resolveLinkedForDocument(array $hostPack): array
    {
        return $this->resolveLinked(
            hostPack: $hostPack,
            expectedRecordType: 'document',
            expectedClass: Document::class,
            label: 'Contacto',
            partyResolver: fn (Document $record) => $record->party,
        );
    }

private function resolveLinked(
    array $hostPack,
    string $expectedRecordType,
    string $expectedClass,
    string $label,
    callable $partyResolver,
): array {
    [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

    if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
        return [
            'data' => [
                'linked' => [
                    'supported' => false,
                    'exists' => false,
                    'hidden' => true,
                    'readonly' => false,
                    'state' => 'hidden',
                    'show_url' => null,
                    'label' => $label,
                    'trail_query' => [],
                    'text' => $label,
                ],
                'variant' => 'inline',
            ],
        ];
    }

    return [
        'data' => [
            'linked' => PartyLinked::forParty(
                $partyResolver($record),
                $trailQuery,
                $label,
            ),
            'variant' => 'inline',
        ],
    ];
}
}