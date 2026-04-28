<?php

// FILE: app/Support/Ui/HostTabs.php | V1

declare(strict_types=1);

namespace App\Support\Ui;

use Illuminate\Support\Collection;

final class HostTabs
{
    public static function activeKey(Collection|array $items, mixed $requestedTab): ?string
    {
        $items = collect($items)->values();
        $requestedTab = is_string($requestedTab) ? $requestedTab : '';

        $availableKeys = $items
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        if (in_array($requestedTab, $availableKeys, true)) {
            return $requestedTab;
        }

        $firstKey = $items->first()['key'] ?? null;

        return is_string($firstKey) ? $firstKey : null;
    }
}