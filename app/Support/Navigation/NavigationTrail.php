<?php

// FILE: app/Support/Navigation/NavigationTrail.php | V2

namespace App\Support\Navigation;

use Illuminate\Http\Request;

class NavigationTrail
{
    public static function makeNode(string $key, mixed $id, string $label, string $url): array
    {
        return [
            'key' => $key,
            'id' => $id,
            'label' => trim($label),
            'url' => $url,
        ];
    }

    public static function base(array $nodes): array
    {
        return self::normalize($nodes);
    }

    public static function fromRequest(Request $request, string $field = 'trail'): array
    {
        return self::decode($request->query($field));
    }

    public static function encode(array $trail): string
    {
        $json = json_encode(self::normalize($trail), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return '';
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public static function decode(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);

        if (! is_array($data)) {
            return [];
        }

        return self::normalize($data);
    }

    public static function toQuery(array $trail, string $field = 'trail'): array
    {
        $encoded = self::encode($trail);

        return $encoded !== '' ? [$field => $encoded] : [];
    }

    public static function normalize(array $trail): array
    {
        $normalized = [];

        foreach ($trail as $node) {
            if (! is_array($node)) {
                continue;
            }

            $key = isset($node['key']) ? trim((string) $node['key']) : '';
            $label = isset($node['label']) ? trim((string) $node['label']) : '';
            $url = isset($node['url']) ? trim((string) $node['url']) : '';
            $id = $node['id'] ?? null;

            if ($key === '' || $label === '' || $url === '') {
                continue;
            }

            $normalized[] = [
                'key' => $key,
                'id' => $id,
                'label' => $label,
                'url' => $url,
            ];
        }

        $deduped = [];

        foreach ($normalized as $node) {
            $last = end($deduped);

            if ($last !== false && self::sameNode($last, $node)) {
                $deduped[key($deduped)] = $node;

                continue;
            }

            $deduped[] = $node;
        }

        return array_values($deduped);
    }

    public static function append(array $trail, array $node): array
    {
        $trail = self::normalize($trail);
        $node = self::normalize([$node])[0] ?? null;

        if (! $node) {
            return $trail;
        }

        $trail[] = $node;

        return self::normalize($trail);
    }

    public static function appendOrCollapse(array $trail, array $node): array
    {
        $trail = self::normalize($trail);
        $node = self::normalize([$node])[0] ?? null;

        if (! $node) {
            return $trail;
        }

        $existingIndex = self::findIndex($trail, $node['key'], $node['id']);

        if ($existingIndex === null) {
            $trail[] = $node;

            return self::normalize($trail);
        }

        if ($existingIndex === array_key_last($trail)) {
            $trail[$existingIndex] = $node;

            return self::normalize($trail);
        }

        $trail = array_slice($trail, 0, $existingIndex + 1);
        $trail[$existingIndex] = $node;

        return self::normalize($trail);
    }

    public static function sliceBefore(array $trail, string $key, mixed $id = null): array
    {
        $trail = self::normalize($trail);

        foreach ($trail as $index => $node) {
            $sameKey = (string) ($node['key'] ?? '') === $key;
            $sameId = $id === null || (($node['id'] ?? null) == $id);

            if ($sameKey && $sameId) {
                return array_values(array_slice($trail, 0, $index));
            }
        }

        return $trail;
    }

    public static function replaceCurrentUrl(array $trail, string $url): array
    {
        $trail = self::normalize($trail);

        if (empty($trail)) {
            return $trail;
        }

        $lastIndex = array_key_last($trail);
        $trail[$lastIndex]['url'] = $url;

        return $trail;
    }

    public static function current(array $trail): ?array
    {
        $trail = self::normalize($trail);

        return ! empty($trail) ? $trail[array_key_last($trail)] : null;
    }

    public static function previous(array $trail): ?array
    {
        $trail = self::normalize($trail);

        if (count($trail) < 2) {
            return null;
        }

        return $trail[count($trail) - 2];
    }

    public static function previousUrl(array $trail, ?string $fallback = null): ?string
    {
        $previous = self::previous($trail);

        return $previous['url'] ?? $fallback;
    }

    public static function hasNode(array $trail, string $key, mixed $id): bool
    {
        return self::findIndex($trail, $key, $id) !== null;
    }

    public static function findIndex(array $trail, string $key, mixed $id): ?int
    {
        $trail = self::normalize($trail);

        foreach ($trail as $index => $node) {
            if (
                (string) ($node['key'] ?? '') === $key &&
                ($node['id'] ?? null) == $id
            ) {
                return $index;
            }
        }

        return null;
    }

    public static function toBreadcrumbItems(array $trail): array
    {
        return array_map(function (array $node) {
            return [
                'label' => $node['label'],
                'url' => $node['url'],
            ];
        }, self::normalize($trail));
    }

    protected static function sameNode(array $left, array $right): bool
    {
        return
            (string) ($left['key'] ?? '') === (string) ($right['key'] ?? '') &&
            (($left['id'] ?? null) == ($right['id'] ?? null));
    }
}
