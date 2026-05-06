<?php

// FILE: app/Support/Products/ProductLineItemSelector.php | V1

namespace App\Support\Products;

use App\Models\Product;
use App\Support\Auth\Security;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ProductLineItemSelector
{
    public function optionsFor(
        mixed $user,
        string $tenantId,
        bool $enabled = true,
    ): Collection {
        if (! $enabled) {
            return collect();
        }

        return app(Security::class)
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    public function nullableRulesFor(
        string $tenantId,
        bool $enabled = true,
    ): array {
        $rules = ['nullable', 'integer'];

        if (! $enabled) {
            return $rules;
        }

        $rules[] = Rule::exists('products', 'id')->where(function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                ->whereNull('deleted_at');
        });

        return $rules;
    }

    public function assertViewable(
        mixed $user,
        string $tenantId,
        int|string|null $productId,
        bool $enabled = true,
    ): void {
        if (! $enabled || empty($productId)) {
            return;
        }

        app(Security::class)
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($productId)
            ->firstOrFail();
    }

    public function normalizeProductId(
        int|string|null $productId,
        bool $enabled = true,
    ): int|string|null {
        if (! $enabled) {
            return null;
        }

        return $productId;
    }
}