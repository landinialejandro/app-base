<?php

// FILE: app/Http/Requests/Attachments/StoreAttachmentRequest.php | V1

namespace App\Http\Requests\Attachments;

use App\Support\Attachments\AttachmentAllowedParents;
use App\Support\Attachments\AttachmentCategory;
use App\Support\Attachments\AttachmentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', 'string', Rule::in(AttachmentAllowedParents::classes())],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,txt', 'max:15360'],
            'kind' => ['required', 'string', Rule::in(AttachmentKind::values())],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(AttachmentCategory::values())],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'return_to' => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function normalizedData(): array
    {
        $kind = (string) $this->string('kind');

        return [
            'attachable_type' => (string) $this->string('attachable_type'),
            'attachable_id' => (int) $this->integer('attachable_id'),
            'kind' => $kind,
            'title' => $this->normalizeText($this->input('title'), 255),
            'category' => $this->normalizeText($this->input('category'), 100)
                ?: AttachmentKind::defaultCategory($kind),
            'description' => $this->normalizeText($this->input('description'), 2000),
            'sort_order' => $this->filled('sort_order') ? (int) $this->integer('sort_order') : 0,
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

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo seleccionado no es válido.',
            'file.mimes' => 'El archivo debe ser una imagen JPG, JPEG, PNG o WEBP, o un archivo PDF o TXT.',
            'file.max' => 'El archivo no puede superar los 15 MB.',
            'kind.required' => 'Debes indicar el tipo de adjunto.',
            'kind.in' => 'El tipo de adjunto seleccionado no es válido.',
            'category.in' => 'La categoría seleccionada no es válida.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
        ];
    }
}
