<?php

// FILE: app/Http/Requests/UpdateShopItemRequest.php | V1

namespace App\Http\Requests;

use App\Models\Shop;
use App\Models\ShopItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shop = $this->route('shop');
        $item = $this->route('item');

        return $shop instanceof Shop
            && $item instanceof ShopItem
            && (int) $item->self_service_shop_id === (int) $shop->id
            && $this->user()?->can('update', $shop) === true;
    }

    public function rules(): array
    {
        return [
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
}