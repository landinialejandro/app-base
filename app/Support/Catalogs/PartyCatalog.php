<?php

// FILE: app/Support/Catalogs/PartyCatalog.php | V3

namespace App\Support\Catalogs;

class PartyCatalog extends BaseCatalog
{
    public const KIND_PERSON = 'person';

    public const KIND_COMPANY = 'company';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_SUPPLIER = 'supplier';

    public const ROLE_EMPLOYEE = 'employee';

    public const ROLE_OTHER = 'other';

    protected static array $kinds = [
        self::KIND_PERSON => 'Persona',
        self::KIND_COMPANY => 'Organización',
    ];

    protected static array $roles = [
        self::ROLE_CUSTOMER => 'Cliente',
        self::ROLE_SUPPLIER => 'Proveedor',
        self::ROLE_EMPLOYEE => 'Colaborador',
        self::ROLE_OTHER => 'Otro',
    ];

    public static function roles(): array
    {
        return array_keys(static::$roles);
    }

    public static function roleLabels(): array
    {
        return static::$roles;
    }

    public static function roleLabel(?string $role): string
    {
        return static::$roles[$role] ?? '—';
    }
}