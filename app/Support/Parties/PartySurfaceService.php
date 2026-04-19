<?php

// FILE: app/Support/Parties/PartySurfaceService.php | V7

namespace App\Support\Parties;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class PartySurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'party.linked',
                label: AppointmentCatalog::contactLabel(),
                targets: ['appointments.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party-action',
                resolver: $this->resolveLinkedForAppointment(...),
            ),
            $this->linkedOffer(
                key: 'party.asset.linked',
                label: 'Contacto',
                targets: ['assets.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party-action',
                resolver: $this->resolveLinkedForAsset(...),
            ),
            $this->linkedOffer(
                key: 'party.order.linked',
                label: 'Contacto',
                targets: ['orders.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party-action',
                resolver: $this->resolveLinkedForOrder(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host !== 'parties.show' || ! $record instanceof Party) {
            return [];
        }

        return [
            'host' => $host,
            'record' => $record,
            'recordType' => 'party',
            'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
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

    private function resolveLinked(
        array $hostPack,
        string $expectedRecordType,
        string $expectedClass,
        string $label,
        callable $partyResolver,
    ): array {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
            return [
                'data' => [
                    'action' => [
                        'supported' => false,
                        'linked' => false,
                        'can_view' => false,
                        'can_create' => false,
                        'readonly' => false,
                        'hidden' => true,
                        'show_url' => null,
                        'create_url' => null,
                        'label' => $label,
                        'trail_query' => [],
                        'linked_text' => $label,
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => PartyLinkedAction::forParty(
                    $partyResolver($record),
                    $trailQuery,
                    $label,
                ),
                'variant' => 'summary',
            ],
        ];
    }
}
