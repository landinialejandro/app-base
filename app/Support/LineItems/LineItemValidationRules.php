<?php

// FILE: app/Support/LineItems/LineItemValidationRules.php | V1

namespace App\Support\LineItems;

final class LineItemValidationRules
{
    public function baseRules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}