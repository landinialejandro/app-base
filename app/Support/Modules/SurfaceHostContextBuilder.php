<?php

// FILE: app/Support/Modules/SurfaceHostContextBuilder.php | V1

namespace App\Support\Modules;

use Illuminate\Database\Eloquent\Model;

class SurfaceHostContextBuilder
{
    /**
     * @param  array<string, mixed>  $hostPack
     * @return array<string, mixed>
     */
    public function forForm(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;

        $hostPack['mode'] = $hostPack['mode'] ?? (
            $record instanceof Model && $record->exists
                ? 'edit'
                : 'create'
        );

        $modelClass = $hostPack['modelClass'] ?? (
            $record instanceof Model ? $record::class : null
        );

        $fieldDefaults = is_array($hostPack['fieldDefaults'] ?? null)
            ? $hostPack['fieldDefaults']
            : [];

        $fieldNames = is_array($hostPack['fieldNames'] ?? null)
            ? $hostPack['fieldNames']
            : $this->fieldNamesFor($record, $modelClass);

        $existingForm = is_array($hostPack['form'] ?? null)
            ? $hostPack['form']
            : [];

        $existingFields = is_array($existingForm['fields'] ?? null)
            ? $existingForm['fields']
            : [];

        $fields = [];

        foreach ($fieldNames as $fieldName) {
            if (! is_string($fieldName) || $fieldName === '') {
                continue;
            }

            $default = $fieldDefaults[$fieldName] ?? '';

            if ($record instanceof Model && array_key_exists($fieldName, $record->getAttributes())) {
                $default = $record->{$fieldName};
            }

            $fields[$fieldName] = old($fieldName, $default);
        }

        $fields = array_merge($fields, $existingFields);

        $hostPack['form'] = array_merge($existingForm, [
            'fields' => $fields,
            'fieldNames' => array_values($fieldNames),
            'externalCandidates' => $this->externalCandidates($fieldNames),
        ]);

        return $hostPack;
    }

    protected function fieldNamesFor(mixed $record, mixed $modelClass): array
    {
        if ($record instanceof Model) {
            return $record->getFillable();
        }

        if (
            is_string($modelClass)
            && class_exists($modelClass)
            && is_subclass_of($modelClass, Model::class)
        ) {
            return (new $modelClass())->getFillable();
        }

        return [];
    }

    protected function externalCandidates(array $fieldNames): array
    {
        return collect($fieldNames)
            ->filter(fn ($field) => is_string($field) && str_ends_with($field, '_id'))
            ->values()
            ->all();
    }
}