<?php

// FILE: app/Support/Parties/PartySurfaceService.php | V4

namespace App\Support\Parties;

use App\Models\Appointment;
use App\Models\Party;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class PartySurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'linked',
                'key' => 'party.linked',
                'label' => AppointmentCatalog::contactLabel(),
                'targets' => ['appointments.show'],
                'slot' => 'summary_items',
                'priority' => 20,
                'view' => 'parties.components.linked-party-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForAppointment(...),
            ],
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host !== 'parties.show') {
            return [];
        }

        if (! $record instanceof Party) {
            return [];
        }

        return [
            'host' => $host,
            'record' => $record,
            'recordType' => 'party',
            'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
        ];
    }

    private function resolveLinkedForAppointment(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== 'appointment' || ! $record instanceof Appointment) {
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
                        'label' => AppointmentCatalog::contactLabel(),
                        'trail_query' => [],
                        'linked_text' => AppointmentCatalog::contactLabel(),
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => PartyLinkedAction::forParty(
                    $record->party,
                    $trailQuery,
                    AppointmentCatalog::contactLabel(),
                ),
                'variant' => 'summary',
            ],
        ];
    }
}
