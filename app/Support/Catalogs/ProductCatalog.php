<?php

// FILE: app/Support/Catalogs/ProductCatalog.php

namespace App\Support\Catalogs;

class ProductCatalog extends BaseCatalog
{
    public const KIND_PRODUCT = 'product';
    public const KIND_SERVICE = 'service';

    protected static array $kinds = [
        self::KIND_PRODUCT => 'Producto',
        self::KIND_SERVICE => 'Servicio',
    ];
}
