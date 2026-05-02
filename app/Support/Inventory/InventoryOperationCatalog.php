<?php

// FILE: app/Support/Inventory/InventoryOperationCatalog.php | V1

namespace App\Support\Inventory;

class InventoryOperationCatalog
{
    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public const TYPE_ORDER_LINE_EXECUTE = 'order_line_execute';

    public const TYPE_ORDER_LINE_RETURN = 'order_line_return';

    public const TYPE_DOCUMENT_MOVEMENT = 'document_movement';

    public static function types(): array
    {
        return [
            self::TYPE_MANUAL_ADJUSTMENT,
            self::TYPE_ORDER_LINE_EXECUTE,
            self::TYPE_ORDER_LINE_RETURN,
            self::TYPE_DOCUMENT_MOVEMENT,
        ];
    }

    public static function labels(): array
    {
        return [
            self::TYPE_MANUAL_ADJUSTMENT => 'Ajuste manual',
            self::TYPE_ORDER_LINE_EXECUTE => 'Ejecución de línea',
            self::TYPE_ORDER_LINE_RETURN => 'Devolución de línea',
            self::TYPE_DOCUMENT_MOVEMENT => 'Movimiento documental',
        ];
    }

    public static function label(?string $type): string
    {
        return self::labels()[$type] ?? 'Operación';
    }


    public static function activityTrackedFields(): array
    {
        return [
            'operation_type',
            'origin_type',
            'origin_id',
            'origin_line_type',
            'origin_line_id',
            'notes',
        ];
    }
}
