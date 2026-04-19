<?php

// FILE: app/Support/Assets/AssetSurfaceService.php | V4

namespace App\Support\Assets;

use App\Models\Appointment;
use App\Models\Asset;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class AssetSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            [
                'type' => 'linked',
                'key' => 'asset.linked',
                'label' => AppointmentCatalog::assetLabel(),
                'targets' => ['appointments.show'],
                'slot' => 'summary_items',
                'priority' => 30,
                'view' => 'assets.components.linked-asset-action',
                'needs' => ['record', 'recordType', 'trailQuery'],
                'resolver' => $this->resolveLinkedForAppointment(...),
            ],
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host !== 'assets.show') {
            return [];
        }

        if (! $record instanceof Asset) {
            return [];
        }

        return [
            'host' => $host,
            'record' => $record,
            'recordType' => 'asset',
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
                        'show_url' => null,
                        'label' => AppointmentCatalog::assetLabel(),
                        'linked_text' => '—',
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'action' => AssetLinkedAction::forAsset(
                    $record->asset,
                    $trailQuery,
                    AppointmentCatalog::assetLabel(),
                ),
                'variant' => 'summary',
            ],
        ];
    }
}
