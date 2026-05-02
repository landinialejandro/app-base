<?php

// FILE: app/Http/Requests/UpdatePartyRequest.php | V7

namespace App\Http\Requests;

use App\Support\Catalogs\PartyCatalog;
use App\Support\Parties\PartyEmployeeContactAuthorization;
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
            'kind' => ['required', 'string', Rule::in(PartyCatalog::kinds())],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(PartyCatalog::roles())],
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
            $roles = $this->input('roles', []);

            if (! is_array($roles)) {
                return;
            }

            if (
                in_array(PartyCatalog::ROLE_EMPLOYEE, $roles, true)
                && ! app(PartyEmployeeContactAuthorization::class)->allows($this->user())
                && ! ($party && $party->hasActiveMembership())
            ) {
                $validator->errors()->add(
                    'roles',
                    'Solo usuarios autorizados pueden cargar contactos como empleados o colaboradores.'
                );
            }

            if (! $party || ! $party->hasActiveMembership()) {
                return;
            }

            $submittedEmail = trim((string) $this->input('email', ''));
            $currentEmail = trim((string) $party->email);

            if ($submittedEmail !== $currentEmail) {
                $validator->errors()->add(
                    'email',
                    'Este contacto está vinculado a un usuario activo de la empresa. El email no se edita desde esta pantalla.'
                );
            }

            $submittedKind = trim((string) $this->input('kind', ''));

            if ($submittedKind !== PartyCatalog::KIND_PERSON) {
                $validator->errors()->add(
                    'kind',
                    'Este contacto está vinculado a un usuario activo de la empresa. El tipo de contacto debe permanecer como persona.'
                );
            }

            if (! in_array(PartyCatalog::ROLE_EMPLOYEE, $roles, true)) {
                $validator->errors()->add(
                    'roles',
                    'Este contacto está vinculado a un usuario activo de la empresa. Debe conservar la relación colaborador.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $roles = $this->input('roles', []);

        if (! is_array($roles)) {
            $roles = [];
        }

        $this->merge([
            'kind' => is_string($this->input('kind')) ? trim($this->input('kind')) : $this->input('kind'),
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
            'roles' => array_values(array_unique(array_filter(array_map(
                fn ($role) => is_string($role) ? trim($role) : $role,
                $roles
            )))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}