<?php

// FILE: app/Support/Modules/Concerns/BuildsHostPacks.php | V2

namespace App\Support\Modules\Concerns;

trait BuildsHostPacks
{
    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        $supportedHost = $this->supportedHosts()[$host] ?? null;

        if (! is_array($supportedHost)) {
            return [];
        }

        $expectedClass = $supportedHost['class'] ?? null;
        $allowNullRecord = (bool) ($supportedHost['allowNullRecord'] ?? false);

        if (! is_string($expectedClass)) {
            return [];
        }

        if ($record === null && ! $allowNullRecord) {
            return [];
        }

        if ($record !== null && ! $record instanceof $expectedClass) {
            return [];
        }

        $hostPack = [
            'host' => $host,
            'recordType' => $supportedHost['recordType'],
            'record' => $record,
            'trailQuery' => is_array($context['trailQuery'] ?? null)
                ? $context['trailQuery']
                : [],
        ];

        foreach ($context as $key => $value) {
            if ($key === 'trailQuery' || array_key_exists($key, $hostPack)) {
                continue;
            }

            $hostPack[$key] = $value;
        }

        return $hostPack;
    }
}