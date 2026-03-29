<?php

// FILE: app/Http/Requests/Attachments/StoreAttachmentRequest.php | V4

namespace App\Http\Requests\Attachments;

use App\Support\Attachments\AttachmentAllowedParents;
use App\Support\Catalogs\AttachmentCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachable_type' => [
                'required',
                'string',
                Rule::in(AttachmentAllowedParents::types()),
            ],
            'attachable_id' => [
                'required',
                'string',
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf,txt',
                'max:10240',
            ],
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
