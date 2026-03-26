<?php

// FILE: app/Http/Requests/Attachments/UpdateAttachmentRequest.php | V3

namespace App\Http\Requests\Attachments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
