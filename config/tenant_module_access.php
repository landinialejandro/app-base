<?php

use App\Support\Catalogs\ModuleCatalog;

return [

    /*
    |--------------------------------------------------------------------------
    | Habilitación global de módulos
    |--------------------------------------------------------------------------
    |
    | Esta capa funciona como override general del sistema.
    | Sirve como solución transitoria para cambios rápidos equivalentes
    | a una decisión de superadmin sin tocar código de clases.
    |
    | Ejemplo:
    |
    | ModuleCatalog::DOCUMENTS => false,
    |
    */

    'global' => [
        // ModuleCatalog::DOCUMENTS => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Habilitación por tenant
    |--------------------------------------------------------------------------
    |
    | Permite overrides rápidos por tenant específico.
    | La clave puede ser:
    | - ID del tenant
    | - slug del tenant
    |
    | Esto es útil como capa operativa temporal antes de tener
    | administración formal editable por owner.
    |
    | Ejemplo:
    |
    | 'acme-demo' => [
    |     ModuleCatalog::PROJECTS => false,
    | ],
    |
    | 12 => [
    |     ModuleCatalog::ORDERS => false,
    | ],
    |
    */

    'tenants' => [
        // 'tenant-slug' => [
        //     ModuleCatalog::PROJECTS => false,
        // ],

        // 1 => [
        //     ModuleCatalog::ORDERS => false,
        // ],
    ],

];
