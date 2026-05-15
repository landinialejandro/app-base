<?php

// FILE: app/Http/Requests/StoreShopRequest.php | V1

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Shop::class) === true;
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