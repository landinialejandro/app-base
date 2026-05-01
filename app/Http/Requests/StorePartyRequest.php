<?php

// FILE: app/Http/Requests/StorePartyRequest.php | V4

namespace App\Http\Requests;

use App\Support\Catalogs\PartyCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartyRequest extends FormRequest
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
            $roles = $this->input('roles', []);

            if (! is_array($roles)) {
                return;
            }

            if (! in_array(PartyCatalog::ROLE_EMPLOYEE, $roles, true)) {
                return;
            }

            if ($this->currentUserCanManageEmployeeContacts()) {
                return;
            }

            $validator->errors()->add(
                'roles',
                'Solo usuarios autorizados pueden cargar contactos como empleados o colaboradores.'
            );
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
            'roles' => array_values(array_unique(array_filter(array_map(
                fn ($role) => is_string($role) ? trim($role) : $role,
                $roles
            )))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    protected function currentUserCanManageEmployeeContacts(): bool
    {
        $tenant = app('tenant');
        $user = $this->user();

        if (! $tenant || ! $user) {
            return false;
        }

        $membership = $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('roles')
            ->first();

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return true;
        }

        return $membership->roles->contains(
            fn ($role) => $role->slug === RoleCatalog::ADMIN
        );
    }
}