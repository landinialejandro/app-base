<?php

// FILE: app/Support/Tenants/OperationalActivityChangePresenter.php | V1

namespace App\Support\Tenants;

use Illuminate\Support\Collection;

class OperationalActivityChangePresenter
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function summary(array $metadata): ?string
    {
        $details = $this->details($metadata);

        if ($details->isEmpty()) {
            return null;
        }

        $count = $details->count();
        $labels = $details
            ->pluck('label')
            ->take(3)
            ->values()
            ->all();

        if ($count === 1) {
            return '1 cambio: '.$labels[0];
        }

        if ($count <= 3) {
            return $count.' cambios: '.implode(', ', $labels);
        }

        return $count.' cambios registrados';
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return \Illuminate\Support\Collection<int, array<string, string>>
     */
    public function details(array $metadata): Collection
    {
        $changes = $metadata['changes'] ?? [];

        if (! is_array($changes) || $changes === []) {
            return collect();
        }

        return collect($changes)
            ->map(function ($change, string $field) {
                $from = is_array($change) ? ($change['from'] ?? null) : null;
                $to = is_array($change) ? ($change['to'] ?? null) : null;

                return [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'from' => $this->stringValue($from),
                    'to' => $this->stringValue($to),
                ];
            })
            ->values();
    }

    protected function fieldLabel(string $field): string
    {
        return [
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'description' => 'Descripción',
            'notes' => 'Notas',
            'name' => 'Nombre',
            'title' => 'Título',
            'display_name' => 'Nombre visible',
            'email' => 'Email',
            'phone' => 'Teléfono',
            'address' => 'Dirección',
            'assigned_user_id' => 'Asignación',
            'subject_user_id' => 'Sujeto',
            'party_roles' => 'Relación con la empresa',
            'is_active' => 'Activo',
            'kind' => 'Tipo',
            'document_type' => 'Tipo de documento',
            'document_number' => 'Número de documento',
            'tax_id' => 'CUIT / Tax ID',
            'due_date' => 'Fecha de vencimiento',
        ][$field] ?? str($field)
            ->replace('_', ' ')
            ->ucfirst()
            ->toString();
    }

    protected function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            $flat = collect($value)
                ->map(fn ($item) => $this->stringValue($item))
                ->filter(fn (string $item) => $item !== '—')
                ->values();

            return $flat->isEmpty()
                ? '—'
                : $flat->implode(', ');
        }

        $text = trim((string) $value);

        return $text === '' ? '—' : $text;
    }
}