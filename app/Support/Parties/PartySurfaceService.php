<?php

// FILE: app/Support/Parties/PartySurfaceService.php | V9

namespace App\Support\Parties;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Models\Task;
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
            $this->linkedOffer(
                key: 'party.task.linked',
                label: 'Contacto',
                targets: ['tasks.show'],
                slot: 'detail_items',
                priority: 20,
                view: 'parties.components.linked-party-action',
                resolver: $this->resolveLinkedForTask(...),
            ),
            $this->linkedOffer(
                key: 'party.document.linked',
                label: 'Contacto',
                targets: ['documents.show'],
                slot: 'summary_items',
                priority: 20,
                view: 'parties.components.linked-party-action',
                resolver: $this->resolveLinkedForDocument(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'parties.show' && $record instanceof Party) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'party',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'tasks.show' && $record instanceof Task) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'task',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        if ($host === 'documents.show' && $record instanceof Document) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'document',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ];
        }

        return [];
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
