<?php

// FILE: app/Support/Modules/Concerns/BuildsSurfaceOffers.php | V1

namespace App\Support\Modules\Concerns;

trait BuildsSurfaceOffers
{
    /**
     * @param  array<int, string>  $targets
     * @param  array<int, string>  $needs
     * @return array<string, mixed>
     */
    protected function linkedOffer(
        string $key,
        string $label,
        array $targets,
        string $slot,
        int $priority,
        string $view,
        callable $resolver,
        array $needs = ['record', 'recordType', 'trailQuery'],
    ): array {
        return [
            'type' => 'linked',
            'key' => $key,
            'label' => $label,
            'targets' => $targets,
            'slot' => $slot,
            'priority' => $priority,
            'view' => $view,
            'needs' => $needs,
            'resolver' => $resolver,
        ];
    }

    /**
     * @param  array<int, string>  $targets
     * @param  array<int, string>  $needs
     * @return array<string, mixed>
     */
    protected function embeddedOffer(
        string $key,
        string $label,
        array $targets,
        string $slot,
        int $priority,
        string $view,
        callable $resolver,
        array $needs = ['record', 'recordType', 'trailQuery'],
    ): array {
        return [
            'type' => 'embedded',
            'key' => $key,
            'label' => $label,
            'targets' => $targets,
            'slot' => $slot,
            'priority' => $priority,
            'view' => $view,
            'needs' => $needs,
            'resolver' => $resolver,
        ];
    }

    /**
     * @param  array<string, mixed>  $hostPack
     * @return array{0:mixed,1:mixed,2:array<string,mixed>}
     */
    protected function unpackHostPack(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];

        return [$record, $recordType, $trailQuery];
    }
}
