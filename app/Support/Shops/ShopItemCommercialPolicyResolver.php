<?php

// FILE: app/Support/Shops/ShopItemCommercialPolicyResolver.php | V1

namespace App\Support\Shops;

use App\Models\ShopItem;

class ShopItemCommercialPolicyResolver
{
    private const PROVIDER_LABELS = [
        'simulated' => 'Entorno simulado',
        'mercado_pago' => 'Mercado Pago',
        'modo' => 'MODO',
    ];

    private const STOCK_POLICY_LABELS = [
        'inherit' => 'Hereda',
        'disabled' => 'Sin control',
        'controlled' => 'Control específico',
    ];

    public function resolve(ShopItem $item): array
    {
        $shopCommercial = $this->shopCommercial($item);
        $itemCommercial = $this->itemCommercial($item);

        $stockPolicyMode = $this->stockPolicyMode($itemCommercial['stock_policy_mode']);

        return [
            'checkout_enabled' => $shopCommercial['checkout_enabled'],
            'allow_cart' => $itemCommercial['allow_cart'],
            'allow_direct_purchase' => $shopCommercial['direct_purchase_enabled']
                && $itemCommercial['allow_direct_purchase'],
            'provider_target' => $shopCommercial['provider_target'],
            'stock_policy_mode' => $stockPolicyMode,
            'effective_stock_control_enabled' => $this->effectiveStockControlEnabled(
                $stockPolicyMode,
                $shopCommercial['stock_control_enabled'],
            ),
            'shop_stock_limit' => $itemCommercial['shop_stock_limit'],
            'stock_target_quantity' => $itemCommercial['stock_target_quantity'],
            'stock_protected_quantity' => $itemCommercial['stock_protected_quantity'],
            'allow_target_margin' => $this->allowTargetMargin(
                $stockPolicyMode,
                $shopCommercial['allow_stock_margin'],
                $itemCommercial['allow_target_margin'],
            ),
            'max_quantity_per_checkout' => $itemCommercial['max_quantity_per_checkout'],
            'labels' => [
                'provider_target' => self::PROVIDER_LABELS[$shopCommercial['provider_target']],
                'stock_policy_mode' => self::STOCK_POLICY_LABELS[$stockPolicyMode],
            ],
        ];
    }

    private function shopCommercial(ShopItem $item): array
    {
        $commercial = data_get($item->shop?->meta, 'commercial', []);
        $commercial = is_array($commercial) ? $commercial : [];

        $providerTarget = $commercial['provider_target'] ?? 'simulated';
        $providerTarget = array_key_exists($providerTarget, self::PROVIDER_LABELS)
            ? $providerTarget
            : 'simulated';

        return [
            'checkout_enabled' => $this->boolValue($commercial['checkout_enabled'] ?? false),
            'direct_purchase_enabled' => $this->boolValue($commercial['direct_purchase_enabled'] ?? false),
            'stock_control_enabled' => $this->boolValue($commercial['stock_control_enabled'] ?? false),
            'allow_stock_margin' => $this->boolValue($commercial['allow_stock_margin'] ?? false),
            'provider_target' => $providerTarget,
        ];
    }

    private function itemCommercial(ShopItem $item): array
    {
        $commercial = data_get($item->meta, 'commercial', []);
        $commercial = is_array($commercial) ? $commercial : [];

        return [
            'allow_cart' => $this->boolValue($commercial['allow_cart'] ?? true),
            'allow_direct_purchase' => $this->boolValue($commercial['allow_direct_purchase'] ?? false),
            'stock_policy_mode' => $commercial['stock_policy_mode'] ?? 'inherit',
            'shop_stock_limit' => $this->nullableFloat($commercial['shop_stock_limit'] ?? null),
            'stock_target_quantity' => $this->nullableFloat($commercial['stock_target_quantity'] ?? null),
            'stock_protected_quantity' => $this->nullableFloat($commercial['stock_protected_quantity'] ?? null),
            'allow_target_margin' => $this->boolValue($commercial['allow_target_margin'] ?? false),
            'max_quantity_per_checkout' => $this->nullableInt($commercial['max_quantity_per_checkout'] ?? null),
        ];
    }

    private function stockPolicyMode(mixed $value): string
    {
        return array_key_exists($value, self::STOCK_POLICY_LABELS)
            ? (string) $value
            : 'inherit';
    }

    private function effectiveStockControlEnabled(string $stockPolicyMode, bool $shopStockControlEnabled): bool
    {
        return match ($stockPolicyMode) {
            'disabled' => false,
            'controlled' => true,
            default => $shopStockControlEnabled,
        };
    }

    private function allowTargetMargin(
        string $stockPolicyMode,
        bool $shopAllowStockMargin,
        bool $itemAllowTargetMargin,
    ): bool {
        return match ($stockPolicyMode) {
            'disabled' => false,
            'controlled' => $itemAllowTargetMargin,
            default => $shopAllowStockMargin,
        };
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
