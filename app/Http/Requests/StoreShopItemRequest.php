<?php

// FILE: app/Http/Requests/StoreShopItemRequest.php | V2

namespace App\Http\Requests;

use App\Models\Shop;
use App\Models\ShopItem;
use App\Support\Products\ProductLineItemSelector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShopItemRequest extends FormRequest
{
    private const STOCK_POLICY_MODES = [
        'inherit',
        'disabled',
        'controlled',
    ];

    public function authorize(): bool
    {
        $shop = $this->route('shop');

        return $shop instanceof Shop
            && $this->user()?->can('update', $shop) === true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'product_id' => app(ProductLineItemSelector::class)->requiredRulesFor(
                tenantId: $tenant->id,
                activeOnly: true,
            ),
            'display_name' => ['nullable', 'string', 'max:160'],
            'display_description' => ['nullable', 'string', 'max:2000'],
            'use_product_price' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'commercial' => ['sometimes', 'array'],
            'commercial.allow_cart' => ['sometimes', 'boolean'],
            'commercial.allow_direct_purchase' => ['sometimes', 'boolean'],
            'commercial.stock_policy_mode' => ['sometimes', 'string', Rule::in(self::STOCK_POLICY_MODES)],
            'commercial.shop_stock_limit' => ['nullable', 'numeric', 'min:0'],
            'commercial.stock_target_quantity' => ['nullable', 'numeric', 'min:0'],
            'commercial.stock_protected_quantity' => ['nullable', 'numeric', 'min:0'],
            'commercial.allow_target_margin' => ['sometimes', 'boolean'],
            'commercial.max_quantity_per_checkout' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    ShopItem::STATUS_DRAFT,
                    ShopItem::STATUS_PUBLISHED,
                    ShopItem::STATUS_HIDDEN,
                ]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $targetQuantity = $this->input('commercial.stock_target_quantity');
            $protectedQuantity = $this->input('commercial.stock_protected_quantity');

            if (
                $targetQuantity !== null
                && $targetQuantity !== ''
                && $protectedQuantity !== null
                && $protectedQuantity !== ''
                && is_numeric($targetQuantity)
                && is_numeric($protectedQuantity)
                && (float) $targetQuantity < (float) $protectedQuantity
            ) {
                $validator->errors()->add(
                    'commercial.stock_target_quantity',
                    'El stock objetivo debe ser mayor o igual que el stock protegido.'
                );
            }
        });
    }

    public function validatedData(): array
    {
        return $this->normalizedData($this->validated());
    }

    protected function normalizedData(array $data): array
    {
        $data['use_product_price'] = (bool) ($data['use_product_price'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($data['use_product_price'] === true) {
            $data['price'] = null;
        }

        $data['meta'] = [
            'commercial' => $this->commercialData($data),
        ];
        unset($data['commercial']);

        $data['is_visible'] = $data['status'] === ShopItem::STATUS_PUBLISHED;

        return $data;
    }

    protected function commercialData(array $data): array
    {
        if (! $this->has('commercial')) {
            $commercial = [];
        } else {
            $commercial = $data['commercial'] ?? [];
        }

        return [
            'allow_cart' => $this->has('commercial') ? $this->boolean('commercial.allow_cart') : true,
            'allow_direct_purchase' => $this->has('commercial')
                ? $this->boolean('commercial.allow_direct_purchase')
                : false,
            'stock_policy_mode' => $commercial['stock_policy_mode'] ?? 'inherit',
            'shop_stock_limit' => $this->nullableFloat($commercial['shop_stock_limit'] ?? null),
            'stock_target_quantity' => $this->nullableFloat($commercial['stock_target_quantity'] ?? null),
            'stock_protected_quantity' => $this->nullableFloat($commercial['stock_protected_quantity'] ?? null),
            'allow_target_margin' => $this->has('commercial')
                ? $this->boolean('commercial.allow_target_margin')
                : false,
            'max_quantity_per_checkout' => $this->nullableInt($commercial['max_quantity_per_checkout'] ?? null),
        ];
    }

    protected function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    protected function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

}
