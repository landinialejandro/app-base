<?php

// FILE: app/Support/Modules/Concerns/ResolvesRelatedEmbeddedSurfaces.php | V1

namespace App\Support\Modules\Concerns;

use App\Support\Auth\Security;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait ResolvesRelatedEmbeddedSurfaces
{
    protected function relatedEmbeddedSurfacePayload(
        array $hostPack,
        array $relatedHosts,
        array $emptyConfig,
        callable $recordsResolver,
        callable $payloadBuilder,
    ): array {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        $config = is_string($recordType)
            ? ($relatedHosts[$recordType] ?? null)
            : null;

        if (! $this->matchesRelatedSurfaceHost($record, $config)) {
            return $payloadBuilder(
                collect(),
                $emptyConfig,
                $trailQuery,
            );
        }

        return $payloadBuilder(
            $recordsResolver($record, $config),
            $config,
            $trailQuery,
        );
    }

    protected function matchesRelatedSurfaceHost(mixed $record, mixed $config): bool
    {
        if (! is_array($config)) {
            return false;
        }

        $expectedClass = $config['class'] ?? null;

        return is_string($expectedClass) && $record instanceof $expectedClass;
    }

    protected function scopedRelatedRecordsFor(
        Model $record,
        array $config,
        string $ability,
        string $modelClass,
    ): Collection {
        $filterColumn = $config['filterColumn'] ?? null;

        if (! is_string($filterColumn) || $filterColumn === '') {
            return collect();
        }

        if (! is_string($modelClass) || ! method_exists($modelClass, 'query')) {
            return collect();
        }

        return app(Security::class)
            ->scope(auth()->user(), $ability, $modelClass::query())
            ->where($filterColumn, $record->getKey())
            ->latest()
            ->get();
    }

    protected function embeddedRecordsPayload(
        Collection $records,
        string $recordsKey,
        array $config,
        array $trailQuery,
        array $extraData = [],
    ): array {
        return [
            'count' => $records->count(),
            'data' => array_merge([
                $recordsKey => $records,
                'tabsId' => $config['tabsId'] ?? 'embedded-tabs-empty',
                'trailQuery' => $trailQuery,
                'emptyMessage' => $config['emptyMessage'] ?? 'No hay registros para mostrar.',
            ], $extraData),
        ];
    }
}