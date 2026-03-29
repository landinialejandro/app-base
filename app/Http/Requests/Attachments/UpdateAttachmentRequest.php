<?php

// FILE: app/Http/Requests/Attachments/UpdateAttachmentRequest.php | V4

namespace App\Http\Requests\Attachments;

use App\Support\Catalogs\AttachmentCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttachmentRequest extends FormRequest
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
                Rule::in(AttachmentCatalog::kinds()),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'return_to' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }
}
