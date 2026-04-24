<?php

// FILE: app/Support/Inventory/InventoryOriginCatalog.php | V1

namespace App\Support\Inventory;

class InventoryOriginCatalog
{
    public const TYPE_MANUAL = 'manual';

    public const TYPE_ORDER = 'order';

    public const TYPE_DOCUMENT = 'document';

    public const LINE_TYPE_ORDER_ITEM = 'order_item';

    public const LINE_TYPE_DOCUMENT_ITEM = 'document_item';

    public static function originTypes(): array
    {
        return [
            self::TYPE_MANUAL,
            self::TYPE_ORDER,
            self::TYPE_DOCUMENT,
        ];
    }

    public static function originLineTypes(): array
    {
        return [
            self::LINE_TYPE_ORDER_ITEM,
            self::LINE_TYPE_DOCUMENT_ITEM,
        ];
    }
}