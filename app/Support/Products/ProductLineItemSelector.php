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
        bool $activeOnly = false,
    ): Collection {
        if (! $enabled) {
            return collect();
        }

        $query = app(Security::class)
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query
            ->orderBy('name')
            ->get();
    }

    public function requiredRulesFor(
        string $tenantId,
        bool $enabled = true,
        bool $activeOnly = false,
    ): array {
        $rules = ['required', 'integer'];

        if (! $enabled) {
            return $rules;
        }

        $rules[] = Rule::exists('products', 'id')->where(function ($query) use ($tenantId, $activeOnly) {
            $query
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at');

            if ($activeOnly) {
                $query->where('is_active', true);
            }
        });

        return $rules;
    }

    public function nullableRulesFor(
        string $tenantId,
        bool $enabled = true,
        bool $activeOnly = false,
    ): array {
        $rules = ['nullable', 'integer'];

        if (! $enabled) {
            return $rules;
        }

        $rules[] = Rule::exists('products', 'id')->where(function ($query) use ($tenantId, $activeOnly) {
            $query
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at');

            if ($activeOnly) {
                $query->where('is_active', true);
            }
        });

        return $rules;
    }

    public function assertViewable(
        mixed $user,
        string $tenantId,
        int|string|null $productId,
        bool $enabled = true,
        bool $activeOnly = false,
    ): void {
        if (! $enabled || empty($productId)) {
            return;
        }

        $query = app(Security::class)
            ->scope($user, 'products.viewAny', Product::query())
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($productId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $query->firstOrFail();
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