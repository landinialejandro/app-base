<?php

// FILE: app/Http/Requests/StoreSelfServiceCustomerRegistrationRequest.php | V2

namespace App\Http\Requests;

use App\Models\Party;
use App\Models\SelfServiceCustomerRegistration;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class StoreSelfServiceCustomerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('tenant') instanceof Tenant;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'document_number' => [
                'required',
                'string',
                'max:100',
                'regex:/^[0-9]{6,12}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->documentNumberExistsForTenant((string) $value)) {
                        $fail('Ya existe un contacto registrado con ese DNI en esta tienda.');
                    }
                },
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->emailExistsForTenant((string) $value)) {
                        $fail('Ya existe un contacto registrado con ese email en esta tienda.');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'max:100',
                'regex:/^\+?[0-9]{8,20}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->phoneExistsForTenant((string) $value)) {
                        $fail('Ya existe un contacto registrado con ese teléfono en esta tienda.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingresá tu nombre.',
            'document_number.required' => 'Ingresá tu DNI.',
            'document_number.regex' => 'Ingresá un DNI válido, solo con números.',
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'Ingresá un email válido.',
            'phone.required' => 'Ingresá tu teléfono.',
            'phone.regex' => 'Ingresá el teléfono solo con números y, si corresponde, el prefijo internacional con +.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = is_string($this->input('email'))
            ? mb_strtolower(trim($this->input('email')))
            : $this->input('email');

        $phone = is_string($this->input('phone'))
            ? preg_replace('/\s+|-|\(|\)/', '', trim($this->input('phone')))
            : $this->input('phone');

        $documentNumber = is_string($this->input('document_number'))
            ? preg_replace('/\D+/', '', trim($this->input('document_number')))
            : $this->input('document_number');

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'display_name' => is_string($this->input('display_name')) ? trim($this->input('display_name')) : $this->input('display_name'),
            'document_number' => $documentNumber,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    protected function tenant(): Tenant
    {
        return $this->route('tenant');
    }

    protected function emailExistsForTenant(string $email): bool
    {
        $email = mb_strtolower(trim($email));

        return $this->pendingRegistrationExists('email', $email)
            || Party::query()
                ->where('tenant_id', $this->tenant()->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists();
    }

    protected function phoneExistsForTenant(string $phone): bool
    {
        return $this->pendingRegistrationExists('phone', $phone)
            || Party::query()
                ->where('tenant_id', $this->tenant()->id)
                ->where('phone', $phone)
                ->exists();
    }

    protected function documentNumberExistsForTenant(string $documentNumber): bool
    {
        return $this->pendingRegistrationExists('document_number', $documentNumber)
            || Party::query()
                ->where('tenant_id', $this->tenant()->id)
                ->where('document_number', $documentNumber)
                ->exists();
    }

    protected function pendingRegistrationExists(string $column, string $value): bool
    {
        return SelfServiceCustomerRegistration::query()
            ->where('tenant_id', $this->tenant()->id)
            ->where($column, $value)
            ->where('status', SelfServiceCustomerRegistration::STATUS_PENDING)
            ->exists();
    }
}