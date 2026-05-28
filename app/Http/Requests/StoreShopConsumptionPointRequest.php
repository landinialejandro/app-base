<?php

// FILE: app/Http/Requests/StoreShopConsumptionPointRequest.php | V1

namespace App\Http\Requests;

use App\Models\Shop;
use App\Models\ShopConsumptionPoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopConsumptionPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shop = $this->route('shop');

        return $shop instanceof Shop
            && $this->user()?->can('update', $shop) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in([
                ShopConsumptionPoint::STATUS_ACTIVE,
                ShopConsumptionPoint::STATUS_INACTIVE,
            ])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validatedData(): array
    {
        $data = $this->validated();
        $data['status'] = $data['status'] ?? ShopConsumptionPoint::STATUS_ACTIVE;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
