<?php

namespace App\Support\Catalogs;

class DocumentCatalog extends BaseCatalog
{
    public const KIND_QUOTE = 'quote';
    public const KIND_INVOICE = 'invoice';
    public const KIND_DELIVERY_NOTE = 'delivery_note';
    public const KIND_WORK_ORDER = 'work_order';
    public const KIND_RECEIPT = 'receipt';
    public const KIND_CREDIT_NOTE = 'credit_note';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    protected static array $kinds = [
        self::KIND_QUOTE => 'Presupuesto',
        self::KIND_INVOICE => 'Factura',
        self::KIND_DELIVERY_NOTE => 'Remito',
        self::KIND_WORK_ORDER => 'Orden de trabajo',
        self::KIND_RECEIPT => 'Recibo',
        self::KIND_CREDIT_NOTE => 'Nota de crédito',
    ];

    protected static array $statuses = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_ISSUED => 'Emitido',
        self::STATUS_CANCELLED => 'Cancelado',
    ];
}
