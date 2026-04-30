<?php

// FILE: app/Http/Requests/UpdatePartyRequest.php | V3

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

public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        $party = $this->route('party');

        if (! $party) {
            return;
        }

        $hasLinkedMembership = $party->memberships()->exists();

        if (! $hasLinkedMembership) {
            return;
        }

        $submittedEmail = trim((string) $this->input('email', ''));
        $currentEmail = trim((string) $party->email);

        if ($submittedEmail !== $currentEmail) {
            $validator->errors()->add(
                'email',
                'El email de un contacto vinculado a un usuario no puede modificarse desde esta pantalla.'
            );
        }

        $submittedKind = trim((string) $this->input('kind', ''));

        if ($submittedKind !== PartyCatalog::KIND_EMPLOYEE) {
            $validator->errors()->add(
                'kind',
                'El tipo de un contacto vinculado a un usuario del tenant debe permanecer como colaborador.'
            );
        }
    });
}

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kind' => is_string($this->input('kind')) ? trim($this->input('kind')) : $this->input('kind'),
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
