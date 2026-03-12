<?php

// FILE: app/Support/Catalogs/PartyCatalog.php

namespace App\Support\Catalogs;

class PartyCatalog extends BaseCatalog
{
    public const KIND_CUSTOMER = 'customer';
    public const KIND_SUPPLIER = 'supplier';
    public const KIND_PERSON = 'person';
    public const KIND_COMPANY = 'company';
    public const KIND_EMPLOYEE = 'employee';

    protected static array $kinds = [
        self::KIND_CUSTOMER => 'Cliente',
        self::KIND_SUPPLIER => 'Proveedor',
        self::KIND_PERSON => 'Persona',
        self::KIND_COMPANY => 'Empresa',
        self::KIND_EMPLOYEE => 'Empleado',
    ];
}
