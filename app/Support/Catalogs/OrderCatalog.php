<?php

// FILE: app/Support/Catalogs/OrderCatalog.php

namespace App\Support\Catalogs;

class OrderCatalog extends BaseCatalog
{
    public const KIND_SALE = 'sale';
    public const KIND_PURCHASE = 'purchase';
    public const KIND_SERVICE = 'service';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static array $kinds = [
        self::KIND_SALE => 'Venta',
        self::KIND_PURCHASE => 'Compra',
        self::KIND_SERVICE => 'Servicio',
    ];

    protected static array $statuses = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_CONFIRMED => 'Confirmada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];
}

