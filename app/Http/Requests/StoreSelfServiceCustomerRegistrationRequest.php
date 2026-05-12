<?php

// FILE: app/Http/Requests/StoreSelfServiceCustomerRegistrationRequest.php | V3

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
            'display_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->emailExistsForTenant((string) $value)) {
                        $fail('Ya existe un cliente registrado con ese email en esta tienda.');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'max:100',
                'regex:/^\+?[0-9]{8,20}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Ingresá tu nombre para mostrar.',
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'Ingresá un email válido.',
            'phone.required' => 'Ingresá tu teléfono.',
            'phone.regex' => 'Ingresá el teléfono solo con números y, si corresponde, el prefijo internacional con +.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $displayName = is_string($this->input('display_name'))
            ? trim($this->input('display_name'))
            : $this->input('display_name');

        $email = is_string($this->input('email'))
            ? mb_strtolower(trim($this->input('email')))
            : $this->input('email');

        $phone = is_string($this->input('phone'))
            ? preg_replace('/\s+|-|\(|\)/', '', trim($this->input('phone')))
            : $this->input('phone');

        $this->merge([
            'display_name' => $displayName,
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

    protected function pendingRegistrationExists(string $column, string $value): bool
    {
        return SelfServiceCustomerRegistration::query()
            ->where('tenant_id', $this->tenant()->id)
            ->where($column, $value)
            ->where('status', SelfServiceCustomerRegistration::STATUS_PENDING)
            ->exists();
    }
}