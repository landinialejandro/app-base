<?php

// FILE: app/Http/Requests/UpdatePartyRequest.php | V2

namespace App\Http\Requests;

use App\Support\Catalogs\PartyCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => [
                'required',
                'string',
                Rule::in(PartyCatalog::kinds()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kind' => is_string($this->input('kind')) ? trim($this->input('kind')) : $this->input('kind'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
