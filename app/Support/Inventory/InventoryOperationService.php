<?php

// FILE: app/Support/Inventory/InventoryOperationService.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryOperation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class InventoryOperationService
{
public function create(
    string $tenantId,
    string $operationType,
    ?string $originType = null,
    int|string|null $originId = null,
    ?string $originLineType = null,
    int|string|null $originLineId = null,
    ?string $notes = null,
    int|string|null $createdBy = null,
): InventoryOperation {
    $this->validateOperationType($operationType);

    $operation = InventoryOperation::create([
        'tenant_id' => $tenantId,
        'operation_type' => $operationType,
        'origin_type' => $originType,
        'origin_id' => $originId,
        'origin_line_type' => $originLineType,
        'origin_line_id' => $originLineId,
        'notes' => $notes,
        'created_by' => $createdBy,
    ]);

    event(new \App\Events\OperationalRecordCreated(
        record: $operation,
        actorUserId: $createdBy !== null ? (int) $createdBy : null,
    ));

    return $operation;
}

    /**
     * Ejecuta un callback dentro de una operación logística.
     *
     * El callback recibe InventoryOperation y debe crear uno o más movimientos.
     */

public function run(
    string $tenantId,
    string $operationType,
    callable $callback,
    ?string $originType = null,
    int|string|null $originId = null,
    ?string $originLineType = null,
    int|string|null $originLineId = null,
    ?string $notes = null,
    int|string|null $createdBy = null,
): mixed {
    return DB::transaction(function () use (
        $tenantId,
        $operationType,
        $callback,
        $originType,
        $originId,
        $originLineType,
        $originLineId,
        $notes,
        $createdBy,
    ) {
        $operation = app(InventoryOpenOperationResolver::class)->resolve(
            tenantId: $tenantId,
            operationType: $operationType,
            originType: $originType,
            originId: $originId,
            originLineType: $originLineType,
            originLineId: $originLineId,
            notes: $notes,
            createdBy: $createdBy,
        );

        try {
            return $callback($operation);
        } catch (Throwable $e) {
            throw $e;
        }
    });
}

    protected function validateOperationType(string $operationType): void
    {
        if (! in_array($operationType, InventoryOperationCatalog::types(), true)) {
            throw new InvalidArgumentException('Tipo de operación de inventario inválido.');
        }
    }
}