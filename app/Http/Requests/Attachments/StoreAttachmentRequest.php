<?php

// FILE: app/Http/Requests/Attachments/StoreAttachmentRequest.php | V1

namespace App\Http\Requests\Attachments;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
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
            'attachable_type' => [
                'required',
                'string',
                Rule::in($this->allowedAttachableTypes()),
            ],
            'attachable_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,txt',
                'max:15360',
            ],
            'kind' => [
                'nullable',
                'string',
                Rule::in($this->allowedKinds()),
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachable_type.required' => 'Debes indicar el tipo de registro al que se adjunta el archivo.',
            'attachable_type.in' => 'El tipo de registro seleccionado no es válido para adjuntos.',
            'attachable_id.required' => 'Debes indicar el registro al que se adjunta el archivo.',
            'attachable_id.integer' => 'El identificador del registro no es válido.',
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo enviado no es válido.',
            'file.mimes' => 'Solo se permiten archivos JPG, JPEG, PNG, WEBP, PDF o TXT.',
            'file.max' => 'El archivo no debe superar los 15 MB.',
            'kind.in' => 'El tipo de adjunto seleccionado no es válido.',
            'category.max' => 'La categoría no debe superar los 100 caracteres.',
            'title.max' => 'El título no debe superar los 255 caracteres.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',
        ];
    }

    public function validatedAttachableType(): string
    {
        return (string) $this->validated('attachable_type');
    }

    public function validatedAttachableId(): int
    {
        return (int) $this->validated('attachable_id');
    }

    public function validatedKind(): string
    {
        $kind = $this->validated('kind');

        return is_string($kind) && $kind !== '' ? $kind : 'other';
    }

    public function normalizedData(): array
    {
        return [
            'attachable_type' => $this->validatedAttachableType(),
            'attachable_id' => $this->validatedAttachableId(),
            'kind' => $this->validatedKind(),
            'category' => $this->normalizedNullableString('category'),
            'title' => $this->normalizedNullableString('title'),
            'description' => $this->normalizedNullableString('description'),
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedAttachableTypes(): array
    {
        return [
            Asset::class,
            Product::class,
            Project::class,
            Task::class,
            Order::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedKinds(): array
    {
        return [
            'photo',
            'manual',
            'evidence',
            'support',
            'text',
            'other',
        ];
    }

    protected function normalizedNullableString(string $key): ?string
    {
        $value = $this->validated($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
