<?php

// FILE: app/Support/Assets/AssetSurfaceService.php | V9

namespace App\Support\Assets;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AssetSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'asset.linked',
                label: AppointmentCatalog::assetLabel(),
                targets: ['appointments.show'],
                slot: 'summary_items',
                priority: 30,
                view: 'assets.components.linked-asset',
                resolver: $this->resolveLinkedForAppointment(...),
            ),
            $this->linkedOffer(
                key: 'asset.document.linked',
                label: 'Activo',
                targets: ['documents.show'],
                slot: 'detail_items',
                priority: 20,
                view: 'assets.components.linked-asset',
                resolver: $this->resolveLinkedForDocument(...),
            ),
            $this->embeddedOffer(
                key: 'assets.party.embedded',
                label: 'Activos',
                targets: ['parties.show'],
                slot: 'tab_panels',
                priority: 70,
                view: 'assets.partials.embedded-tabs',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'party',
                    expectedClass: Party::class,
                    tabsId: 'party-assets-tabs',
                    emptyTabsId: 'party-assets-tabs-empty',
                ),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
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

        if ($host === 'appointments.show' && $record instanceof Appointment) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'appointment',
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

    private function resolveLinkedForAppointment(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'appointment' || ! $record instanceof Appointment) {
            return [
                'data' => [
                    'linked' => [
                        'supported' => false,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'show_url' => null,
                        'label' => AppointmentCatalog::assetLabel(), // o 'Activo'
                        'text' => '—',
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'linked' => [
                    'supported' => false,
                    'exists' => false,
                    'hidden' => true,
                    'readonly' => false,
                    'state' => 'hidden',
                    'show_url' => null,
                    'label' => 'Activo',
                    'text' => '—',
                ],
                'variant' => 'summary',
            ],
        ];
    }

    private function resolveLinkedForDocument(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'document' || ! $record instanceof Document) {
            return [
                'data' => [
                    'linked' => [
                        'supported' => false,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'show_url' => null,
                        'label' => 'Activo',
                        'text' => '—',
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'linked' => AssetLinked::forAsset(
                    $record->asset,
                    $trailQuery,
                    'Activo',
                ),
                'variant' => 'summary',
            ],
        ];
    }

    private function resolveEmbedded(
        array $hostPack,
        string $expectedRecordType,
        string $expectedClass,
        string $tabsId,
        string $emptyTabsId,
    ): array {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
            return $this->emptyEmbeddedPayload(
                recordType: $expectedRecordType,
                tabsId: $emptyTabsId,
                trailQuery: $trailQuery,
            );
        }

        return $this->buildEmbeddedPayload(
            record: $record,
            recordType: $expectedRecordType,
            tabsId: $tabsId,
            trailQuery: $trailQuery,
        );
    }

    private function buildEmbeddedPayload(
        Model $record,
        string $recordType,
        string $tabsId,
        array $trailQuery,
    ): array {
        $assets = $this->assetsFor($record, $recordType);

        return [
            'count' => $assets->count(),
            'data' => array_merge(
                [
                    'assets' => $assets,
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->assetViewConfig($record, $recordType),
            ),
        ];
    }

    private function emptyEmbeddedPayload(
        string $recordType,
        string $tabsId,
        array $trailQuery,
    ): array {
        return [
            'count' => 0,
            'data' => array_merge(
                [
                    'assets' => collect(),
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->emptyAssetViewConfig($recordType),
            ),
        ];
    }

    private function assetViewConfig(Model $record, string $recordType): array
    {
        return match ($recordType) {
            'party' => [
                'showParty' => false,
                'emptyMessage' => 'Este contacto no tiene activos vinculados.',
                'createBaseQuery' => [
                    'party_id' => $record->getKey(),
                ],
            ],
            default => [],
        };
    }

    private function emptyAssetViewConfig(string $recordType): array
    {
        return match ($recordType) {
            'party' => [
                'showParty' => false,
                'emptyMessage' => 'Este contacto no tiene activos vinculados.',
                'createBaseQuery' => [],
            ],
            default => [],
        };
    }

    private function assetsFor(Model $record, string $recordType): Collection
    {
        if ($recordType === 'party' && $record instanceof Party) {
            return app(Security::class)
                ->scope(auth()->user(), 'assets.viewAny', Asset::query())
                ->with('party')
                ->where('party_id', $record->getKey())
                ->orderBy('name')
                ->get();
        }

        return collect();
    }
}
