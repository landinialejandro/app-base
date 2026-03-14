<?php

// FILE: app/Support/Documents/DocumentNumberGenerator.php

namespace App\Support\Documents;

use App\Models\DocumentSequence;
use Illuminate\Database\QueryException;

class DocumentNumberGenerator
{
    protected static array $defaultPrefixes = [
        'order.sale' => 'ORD',
        'order.purchase' => 'OCO',
        'order.service' => 'OSE',

        'quote' => 'PRE',
        'delivery_note' => 'REM',
        'invoice' => 'FAC',
        'work_order' => 'OTR',
        'receipt' => 'REC',
        'credit_note' => 'NCR',
    ];

    public static function generate(
        string $tenantId,
        string $kind,
        ?string $pointOfSale = null
    ): array {
        $pointOfSale = $pointOfSale ?: '0001';

        $sequence = static::lockSequence(
            tenantId: $tenantId,
            kind: $kind,
            pointOfSale: $pointOfSale,
        );

        $sequenceNumber = (int) $sequence->next_number;
        $prefix = $sequence->prefix;
        $padding = max(1, (int) $sequence->padding);

        $number = static::formatNumber(
            prefix: $prefix,
            pointOfSale: $sequence->point_of_sale,
            sequenceNumber: $sequenceNumber,
            padding: $padding,
        );

        $sequence->next_number = $sequenceNumber + 1;
        $sequence->save();

        return [
            'number' => $number,
            'prefix' => $prefix,
            'point_of_sale' => $sequence->point_of_sale,
            'sequence_number' => $sequenceNumber,
        ];
    }

    protected static function lockSequence(
        string $tenantId,
        string $kind,
        string $pointOfSale
    ): DocumentSequence {
        $sequence = DocumentSequence::query()
            ->where('tenant_id', $tenantId)
            ->where('doc_type', $kind)
            ->where('point_of_sale', $pointOfSale)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            return $sequence;
        }

        try {
            DocumentSequence::create([
                'tenant_id' => $tenantId,
                'branch_id' => null,
                'doc_type' => $kind,
                'prefix' => static::defaultPrefixFor($kind),
                'point_of_sale' => $pointOfSale,
                'padding' => 8,
                'next_number' => 1,
            ]);
        } catch (QueryException $e) {
            // otra transacción pudo crearla
        }

        return DocumentSequence::query()
            ->where('tenant_id', $tenantId)
            ->where('doc_type', $kind)
            ->where('point_of_sale', $pointOfSale)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected static function defaultPrefixFor(string $kind): string
    {
        return static::$defaultPrefixes[$kind] ?? strtoupper(substr($kind, 0, 3));
    }

    protected static function formatNumber(
        string $prefix,
        string $pointOfSale,
        int $sequenceNumber,
        int $padding
    ): string {
        return sprintf(
            '%s-%s-%s',
            strtoupper($prefix),
            $pointOfSale,
            str_pad((string) $sequenceNumber, $padding, '0', STR_PAD_LEFT)
        );
    }
}