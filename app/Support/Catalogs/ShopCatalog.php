<?php

// FILE: app/Support/Catalogs/ShopCatalog.php | V2

namespace App\Support\Catalogs;

use App\Models\Shop;
use App\Models\ShopItem;

final class ShopCatalog
{
    public static function statusLabels(): array
    {
        return [
            Shop::STATUS_DRAFT => 'Borrador',
            Shop::STATUS_ACTIVE => 'Activa',
            Shop::STATUS_INACTIVE => 'Inactiva',
        ];
    }

    public static function statusIntention(?string $value): ?string
    {
        return match ($value) {
            Shop::STATUS_DRAFT => StatusVocabulary::DRAFT,
            Shop::STATUS_ACTIVE => StatusVocabulary::ACTIVE,
            Shop::STATUS_INACTIVE => StatusVocabulary::INACTIVE,
            default => $value,
        };
    }

    public static function statusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::statusLabels()[$value]
            ?? StatusVocabulary::label(static::statusIntention($value), $default);
    }

    public static function badgeClass(?string $value, string $default = 'status-badge--neutral'): string
    {
        return StatusVocabulary::badgeClass(static::statusIntention($value), $default);
    }

    public static function defaultStatus(): string
    {
        return Shop::STATUS_DRAFT;
    }

    public static function isActiveStatus(?string $value): bool
    {
        return $value === Shop::STATUS_ACTIVE;
    }

    public static function itemStatusLabels(): array
    {
        return [
            ShopItem::STATUS_DRAFT => 'Borrador',
            ShopItem::STATUS_PUBLISHED => 'Publicado',
            ShopItem::STATUS_HIDDEN => 'Oculto',
        ];
    }

    public static function itemStatusIntention(?string $value): ?string
    {
        return match ($value) {
            ShopItem::STATUS_DRAFT => StatusVocabulary::DRAFT,
            ShopItem::STATUS_PUBLISHED => StatusVocabulary::PUBLISHED,
            ShopItem::STATUS_HIDDEN => StatusVocabulary::HIDDEN,
            default => $value,
        };
    }

    public static function itemStatusLabel(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return static::itemStatusLabels()[$value]
            ?? StatusVocabulary::label(static::itemStatusIntention($value), $default);
    }

    public static function itemBadgeClass(?string $value, string $default = 'status-badge--neutral'): string
    {
        return StatusVocabulary::badgeClass(static::itemStatusIntention($value), $default);
    }

    public static function defaultItemStatus(): string
    {
        return ShopItem::STATUS_DRAFT;
    }

    public static function itemFilterLabels(): array
    {
        return [
            ShopItem::STATUS_PUBLISHED => 'Publicados',
            ShopItem::STATUS_DRAFT => 'Borradores',
            ShopItem::STATUS_HIDDEN => 'Ocultos',
        ];
    }

    public static function isItemPublishedStatus(?string $value): bool
    {
        return $value === ShopItem::STATUS_PUBLISHED;
    }

    public static function nextItemToggleStatus(?string $value): string
    {
        return static::isItemPublishedStatus($value)
            ? ShopItem::STATUS_HIDDEN
            : ShopItem::STATUS_PUBLISHED;
    }

    public static function publicVisibilityLabel(bool $value): string
    {
        return $value ? 'Visible' : 'No visible';
    }

    public static function publicVisibilityBadgeClass(bool $value): string
    {
        return StatusVocabulary::badgeClass($value ? StatusVocabulary::PUBLISHED : StatusVocabulary::HIDDEN);
    }

    public static function publicVisibilityPresentation(bool $value): array
    {
        return [
            'key' => $value ? StatusVocabulary::PUBLISHED : StatusVocabulary::HIDDEN,
            'label' => static::publicVisibilityLabel($value),
            'badgeClass' => static::publicVisibilityBadgeClass($value),
        ];
    }
}