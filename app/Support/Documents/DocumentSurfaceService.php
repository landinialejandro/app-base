<?php

// FILE: app/Support/Documents/DocumentSurfaceService.php | V7

namespace App\Support\Documents;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Order;
use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DocumentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'documents.order.embedded',
                target: 'orders.show',
                expectedRecordType: 'order',
                expectedClass: Order::class,
                tabsId: 'order-documents-tabs',
                emptyTabsId: 'order-documents-tabs-empty',
                priority: 80,
            ),
            $this->embeddedOffer(
                key: 'documents.asset.embedded',
                target: 'assets.show',
                expectedRecordType: 'asset',
                expectedClass: Asset::class,
                tabsId: 'asset-documents-tabs',
                emptyTabsId: 'asset-documents-tabs-empty',
                priority: 80,
            ),
            $this->embeddedOffer(
                key: 'documents.party.embedded',
                target: 'parties.show',
                expectedRecordType: 'party',
                expectedClass: Party::class,
                tabsId: 'party-documents-tabs',
                emptyTabsId: 'party-documents-tabs-empty',
                priority: 90,
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return match (true) {
            $host === 'orders.show' && $record instanceof Order => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'order',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            $host === 'assets.show' && $record instanceof Asset => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'asset',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            $host === 'parties.show' && $record instanceof Party => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'party',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            $host === 'documents.show' && $record instanceof Document => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'document',
                'trailQuery' => is_array($context['trailQuery'] ?? null) ? $context['trailQuery'] : [],
            ],
            default => [],
        };
    }

    private function embeddedOffer(
        string $key,
        string $target,
        string $expectedRecordType,
        string $expectedClass,
        string $tabsId,
        string $emptyTabsId,
        int $priority,
    ): array {
        return [
            'type' => 'embedded',
            'key' => $key,
            'label' => 'Documentos',
            'targets' => [$target],
            'slot' => 'tab_panels',
            'priority' => $priority,
            'view' => 'documents.partials.embedded-tabs',
            'needs' => ['record', 'recordType', 'trailQuery'],
            'resolver' => fn (array $hostPack) => $this->resolveEmbedded(
                $hostPack,
                expectedRecordType: $expectedRecordType,
                expectedClass: $expectedClass,
                tabsId: $tabsId,
                emptyTabsId: $emptyTabsId,
            ),
        ];
    }

    private function resolveEmbedded(
        array $hostPack,
        string $expectedRecordType,
        string $expectedClass,
        string $tabsId,
        string $emptyTabsId,
    ): array {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
            return $this->emptyEmbeddedPayload(
                recordType: $expectedRecordType,
                tabsId: $emptyTabsId,
                trailQuery: $trailQuery,
            );
        }

        return $this->buildEmbeddedPayload(
            record: $record,
            recordType: $recordType,
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
        $documents = $this->documentsFor($record, $recordType);

        return [
            'count' => $documents->count(),
            'data' => array_merge(
                [
                    'documents' => $documents,
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->documentViewConfig($record, $recordType),
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
                    'documents' => collect(),
                    'tabsId' => $tabsId,
                    'trailQuery' => $trailQuery,
                ],
                $this->emptyDocumentViewConfig($recordType),
            ),
        ];
    }

    private function documentViewConfig(Model $record, string $recordType): array
    {
        return match ($recordType) {
            'order' => [
                'showParty' => true,
                'showAsset' => false,
                'showOrder' => false,
                'emptyMessage' => 'Esta orden no tiene documentos vinculados.',
                'order' => $record,
            ],
            'asset' => [
                'showParty' => true,
                'showAsset' => false,
                'showOrder' => true,
                'emptyMessage' => 'Este activo no tiene documentos vinculados.',
                'createBaseQuery' => [
                    'asset_id' => $record->getKey(),
                    'party_id' => $record->party_id,
                ],
            ],
            'party' => [
                'showParty' => false,
                'showAsset' => true,
                'showOrder' => true,
                'emptyMessage' => 'Este contacto no tiene documentos vinculados.',
                'createBaseQuery' => [
                    'party_id' => $record->getKey(),
                ],
            ],
            default => [],
        };
    }

    private function emptyDocumentViewConfig(string $recordType): array
    {
        return match ($recordType) {
            'order' => [
                'showParty' => true,
                'showAsset' => false,
                'showOrder' => false,
                'emptyMessage' => 'Esta orden no tiene documentos vinculados.',
                'order' => null,
            ],
            'asset' => [
                'showParty' => true,
                'showAsset' => false,
                'showOrder' => true,
                'emptyMessage' => 'Este activo no tiene documentos vinculados.',
                'createBaseQuery' => [],
            ],
            'party' => [
                'showParty' => false,
                'showAsset' => true,
                'showOrder' => true,
                'emptyMessage' => 'Este contacto no tiene documentos vinculados.',
                'createBaseQuery' => [],
            ],
            default => [],
        };
    }

    private function documentsFor(Model $record, string $recordType): Collection
    {
        if ($recordType === 'party' && $record instanceof Party) {
            return app(Security::class)
                ->scope(auth()->user(), 'documents.viewAny', Document::query())
                ->with(['party', 'order', 'asset', 'items'])
                ->where('party_id', $record->getKey())
                ->latest()
                ->get();
        }

        if ($record->relationLoaded('documents')) {
            $documents = $record->getRelation('documents');

            return $documents instanceof Collection ? $documents : collect($documents);
        }

        if (method_exists($record, 'documents')) {
            return $record->documents()->get();
        }

        return collect();
    }
}
