<?php

// FILE: app/Http/Requests/Attachments/UpdateAttachmentRequest.php | V1

namespace App\Http\Requests\Attachments;

use App\Support\Attachments\AttachmentCategory;
use App\Support\Attachments\AttachmentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', 'string', Rule::in(AttachmentKind::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(AttachmentCategory::values())],
            'description' => ['nullable', 'string', 'max:2000'],
            'return_to' => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function normalizedData(): array
    {
        $kind = (string) $this->string('kind');

        return [
            'kind' => $kind,
            'title' => $this->normalizeText($this->input('title'), 255),
            'category' => $this->normalizeText($this->input('category'), 100)
                ?: AttachmentKind::defaultCategory($kind),
            'description' => $this->normalizeText($this->input('description'), 2000),
            'return_to' => $this->normalizeText($this->input('return_to'), 2000),
        ];
    }

    protected function normalizeText(mixed $value, int $max): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }
}
