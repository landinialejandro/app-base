<?php

// FILE: app/Support/Documents/DocumentTotalsCalculator.php

namespace App\Support\Documents;

use App\Models\Document;

class DocumentTotalsCalculator
{
    public static function calculate(Document $document): array
    {
        $subtotal = (float) $document->items()->sum('line_total');

        // Base mínima actual.
        // En el futuro acá puede entrar lógica de IVA, percepciones,
        // recargos, descuentos o reglas por jurisdicción.
        $taxTotal = 0.0;

        $total = $subtotal + $taxTotal;

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ];
    }

    public static function apply(Document $document): void
    {
        $document->update(static::calculate($document));
    }
}