<?php

// FILE: app/Http/Requests/StoreShopItemRequest.php | V1

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopItemRequest extends FormRequest
{
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
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('deleted_at'),
            ],
            'display_name' => ['nullable', 'string', 'max:160'],
            'display_description' => ['nullable', 'string', 'max:2000'],
            'use_product_price' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
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

        $data['is_visible'] = $data['status'] === ShopItem::STATUS_PUBLISHED;

        return $data;
    }

    public function selectedProduct(): ?Product
    {
        $productId = $this->validated('product_id');

        if (! $productId) {
            return null;
        }

        return Product::query()
            ->where('tenant_id', app('tenant')->id)
            ->whereKey($productId)
            ->first();
    }
}