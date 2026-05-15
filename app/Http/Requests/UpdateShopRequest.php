<?php

// FILE: app/Http/Requests/UpdateShopRequest.php | V1

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopRequest extends FormRequest
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
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}