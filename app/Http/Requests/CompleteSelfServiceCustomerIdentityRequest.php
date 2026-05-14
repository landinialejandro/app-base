<?php

// FILE: app/Http/Requests/CompleteSelfServiceCustomerIdentityRequest.php | V2

namespace App\Http\Requests;

use App\Models\Party;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteSelfServiceCustomerIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('tenant') instanceof Tenant;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'document_type' => ['required', 'string', 'max:50'],

            'document_number' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->documentNumberExistsForTenant((string) $value)) {
                        $fail('Ya existe un cliente registrado con ese documento en esta tienda.');
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
                        $fail('Ya existe un cliente registrado con ese teléfono en esta tienda.');
                    }
                },
            ],

            'password' => ['required', 'confirmed', Password::min(8)],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingresá tu nombre completo.',
            'document_type.required' => 'Seleccioná el tipo de documento.',
            'document_number.required' => 'Ingresá tu número de documento.',
            'phone.required' => 'Ingresá tu teléfono.',
            'phone.regex' => 'Ingresá el teléfono solo con números y, si corresponde, el prefijo internacional con +.',
            'password.required' => 'Creá una contraseña para poder volver a ingresar.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'terms_accepted.accepted' => 'Debés aceptar las condiciones para continuar.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = is_string($this->input('name'))
            ? trim($this->input('name'))
            : $this->input('name');

        $documentType = is_string($this->input('document_type'))
            ? trim($this->input('document_type'))
            : $this->input('document_type');

        $documentNumber = is_string($this->input('document_number'))
            ? preg_replace('/\s+|-|\./', '', trim($this->input('document_number')))
            : $this->input('document_number');

        $phone = is_string($this->input('phone'))
            ? preg_replace('/\s+|-|\(|\)/', '', trim($this->input('phone')))
            : $this->input('phone');

        $this->merge([
            'name' => $name,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'phone' => $phone,
        ]);
    }

    protected function tenant(): Tenant
    {
        return $this->route('tenant');
    }

    protected function currentPartyId(): ?int
    {
        $payload = $this->attributes->get('self_service_external_customer');

        $party = is_array($payload)
            ? ($payload['party'] ?? null)
            : null;

        return $party?->id;
    }

    protected function documentNumberExistsForTenant(string $documentNumber): bool
    {
        $documentNumber = trim($documentNumber);

        return Party::query()
            ->where('tenant_id', $this->tenant()->id)
            ->where('document_number', $documentNumber)
            ->when($this->currentPartyId(), function ($query, int $partyId) {
                $query->where('id', '!=', $partyId);
            })
            ->exists();
    }

    protected function phoneExistsForTenant(string $phone): bool
    {
        $phone = trim($phone);

        return Party::query()
            ->where('tenant_id', $this->tenant()->id)
            ->where('phone', $phone)
            ->when($this->currentPartyId(), function ($query, int $partyId) {
                $query->where('id', '!=', $partyId);
            })
            ->exists();
    }
}