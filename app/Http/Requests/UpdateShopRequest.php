<?php

// FILE: app/Http/Requests/UpdateShopRequest.php | V2

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopRequest extends FormRequest
{
    private const PROVIDER_TARGETS = [
        'simulated',
        'mercado_pago',
        'modo',
    ];

    public function authorize(): bool
    {
        $shop = $this->route('shop');

        return $shop instanceof Shop
            && $this->user()?->can('update', $shop) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    Shop::STATUS_DRAFT,
                    Shop::STATUS_ACTIVE,
                    Shop::STATUS_INACTIVE,
                ]),
            ],
            'commercial' => ['sometimes', 'array'],
            'commercial.checkout_enabled' => ['sometimes', 'boolean'],
            'commercial.direct_purchase_enabled' => ['sometimes', 'boolean'],
            'commercial.stock_control_enabled' => ['sometimes', 'boolean'],
            'commercial.allow_stock_margin' => ['sometimes', 'boolean'],
            'commercial.provider_target' => ['sometimes', 'string', Rule::in(self::PROVIDER_TARGETS)],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();
        $shop = $this->route('shop');
        $meta = $shop instanceof Shop && is_array($shop->meta)
            ? $shop->meta
            : [];

        $meta['commercial'] = $this->commercialData($validated);

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'meta' => $meta,
        ];
    }

    private function commercialData(array $validated): array
    {
        return [
            'checkout_enabled' => $this->boolean('commercial.checkout_enabled'),
            'direct_purchase_enabled' => $this->boolean('commercial.direct_purchase_enabled'),
            'stock_control_enabled' => $this->boolean('commercial.stock_control_enabled'),
            'allow_stock_margin' => $this->boolean('commercial.allow_stock_margin'),
            'provider_target' => data_get($validated, 'commercial.provider_target', 'simulated'),
        ];
    }
}
