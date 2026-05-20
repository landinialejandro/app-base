<?php

// FILE: app/Support/Attachments/AttachmentSurfaceService.php | V13

namespace App\Support\Attachments;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Document;
use App\Models\Order;
use App\Models\Task;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AttachmentSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->embeddedOffer(
                key: 'attachments.order.embedded',
                label: 'Adjuntos',
                targets: ['orders.show'],
                slot: 'tab_panels',
                priority: 90,
                view: 'attachments.partials.embedded',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'order',
                    expectedClass: Order::class,
                    attachableType: 'order',
                    tabsId: 'order-attachments-tabs',
                    emptyTabsId: 'order-attachments-tabs-empty',
                ),
            ),
            $this->embeddedOffer(
                key: 'attachments.asset.embedded',
                label: 'Adjuntos',
                targets: ['assets.show'],
                slot: 'tab_panels',
                priority: 90,
                view: 'attachments.partials.embedded',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'asset',
                    expectedClass: Asset::class,
                    attachableType: 'asset',
                    tabsId: 'asset-attachments-tabs',
                    emptyTabsId: 'asset-attachments-tabs-empty',
                ),
            ),
            $this->embeddedOffer(
                key: 'attachments.task.embedded',
                label: 'Adjuntos',
                targets: ['tasks.show'],
                slot: 'tab_panels',
                priority: 90,
                view: 'attachments.partials.embedded',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'task',
                    expectedClass: Task::class,
                    attachableType: 'task',
                    tabsId: 'task-attachments-tabs',
                    emptyTabsId: 'task-attachments-tabs-empty',
                ),
            ),
            $this->embeddedOffer(
                key: 'attachments.document.embedded',
                label: 'Adjuntos',
                targets: ['documents.show'],
                slot: 'tab_panels',
                priority: 90,
                view: 'attachments.partials.embedded',
                resolver: fn (array $hostPack) => $this->resolveEmbedded(
                    $hostPack,
                    expectedRecordType: 'document',
                    expectedClass: Document::class,
                    attachableType: 'document',
                    tabsId: 'document-attachments-tabs',
                    emptyTabsId: 'document-attachments-tabs-empty',
                ),
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
            $host === 'tasks.show' && $record instanceof Task => [
                'host' => $host,
                'record' => $record,
                'recordType' => 'task',
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

    private function resolveEmbedded(
        array $hostPack,
        string $expectedRecordType,
        string $expectedClass,
        string $attachableType,
        string $tabsId,
        string $emptyTabsId,
    ): array {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== $expectedRecordType || ! $record instanceof $expectedClass) {
            return $this->emptyEmbeddedPayload(
                attachableType: $attachableType,
                tabsId: $emptyTabsId,
                trailQuery: $trailQuery,
            );
        }

        return $this->buildEmbeddedPayload(
            attachable: $record,
            attachableType: $attachableType,
            tabsId: $tabsId,
            trailQuery: $trailQuery,
        );
    }

    private function buildEmbeddedPayload(
        Model $attachable,
        string $attachableType,
        string $tabsId,
        array $trailQuery,
    ): array {
        $attachments = $this->attachmentsFor($attachable);

        return [
            'count' => $attachments->count(),
            'data' => [
                'attachments' => $attachments,
                'attachable' => $attachable,
                'attachableType' => $attachableType,
                'attachableId' => $attachable->getKey(),
                'trailQuery' => $trailQuery,
                'tabsId' => $tabsId,
                'createLabel' => 'Agregar adjunto',
            ],
        ];
    }

    private function emptyEmbeddedPayload(
        string $attachableType,
        string $tabsId,
        array $trailQuery,
    ): array {
        return [
            'count' => 0,
            'data' => [
                'attachments' => collect(),
                'attachable' => null,
                'attachableType' => $attachableType,
                'attachableId' => null,
                'trailQuery' => $trailQuery,
                'tabsId' => $tabsId,
                'createLabel' => 'Agregar adjunto',
            ],
        ];
    }

    public function mediaCollectionFor(Model $attachable, array $options = []): array
    {
        $kind = trim((string) ($options['kind'] ?? ''));
        $isImageFilter = array_key_exists('is_image', $options) ? $options['is_image'] : null;
        $index = max(0, (int) ($options['index'] ?? 0));

        $attachments = $this->attachmentsFor($attachable)
            ->filter(function ($attachment) use ($kind, $isImageFilter): bool {
                if (! $attachment instanceof Attachment) {
                    return false;
                }

                if ($kind !== '' && (string) $attachment->kind !== $kind) {
                    return false;
                }

                if ($isImageFilter !== null && (bool) $attachment->is_image !== (bool) $isImageFilter) {
                    return false;
                }

                return true;
            })
            ->values();

        $all = $attachments
            ->map(fn (Attachment $attachment) => $this->mediaPayload($attachment))
            ->values();

        $images = $attachments
            ->filter(fn (Attachment $attachment) => $attachment->isImage())
            ->map(fn (Attachment $attachment) => $this->mediaPayload($attachment))
            ->values();

        $files = $attachments
            ->reject(fn (Attachment $attachment) => $attachment->isImage())
            ->map(fn (Attachment $attachment) => $this->mediaPayload($attachment))
            ->values();

        return [
            'attachments' => $all,
            'images' => $images,
            'files' => $files,
            'image' => $images->get($index),
            'firstImage' => $images->first(),
            'index' => $index,
            'count' => $all->count(),
        ];
    }

    private function mediaPayload(Attachment $attachment): array
    {
        $canView = auth()->user()?->can('view', $attachment) === true;
        $canDownload = auth()->user()?->can('view', $attachment) === true;

        $extension = strtoupper((string) ($attachment->extension ?: ''));

        return [
            'attachment' => $attachment,
            'id' => $attachment->id,
            'kind' => $attachment->kind,
            'is_image' => $attachment->isImage(),
            'file_name' => $attachment->file_name,
            'extension' => $extension,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'can_view' => $canView,
            'preview_url' => $canView ? route('attachments.preview', $attachment) : null,
            'download_url' => $canDownload ? route('attachments.download', $attachment) : null,
        ];
    }

    private function attachmentsFor(Model $attachable): Collection
    {
        if ($attachable->relationLoaded('attachments')) {
            $attachments = $attachable->getRelation('attachments');

            return $attachments instanceof Collection ? $attachments : collect($attachments);
        }

        if (method_exists($attachable, 'attachments')) {
            return $attachable->attachments()->ordered()->get();
        }

        return collect();
    }
}
