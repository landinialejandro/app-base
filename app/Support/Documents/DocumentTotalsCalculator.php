<?php

// FILE: app/Support/Documents/DocumentTotalsCalculator.php | V2

namespace App\Support\Documents;

use App\Models\Document;
use App\Support\LineItems\LineItemMath;

final class DocumentTotalsCalculator
{
    public function recalculate(Document $document): Document
    {
        $math = app(LineItemMath::class);

        $document->loadMissing('items');

        $subtotal = $document->items->sum(
            fn ($item) => $math->lineTotal($item->quantity, $item->unit_price)
        );

        $subtotal = $math->normalizeMoney($subtotal);
        $taxTotal = $math->normalizeMoney($subtotal * 0);
        $total = $math->normalizeMoney($subtotal + $taxTotal);

        $document->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ])->saveQuietly();

        return $document->fresh();
    }
}