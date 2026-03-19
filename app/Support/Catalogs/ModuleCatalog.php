<?php

namespace App\Support\Catalogs;

class ModuleCatalog
{
    public const DASHBOARD = 'dashboard';

    public const PROJECTS = 'projects';

    public const TASKS = 'tasks';

    public const PARTIES = 'parties';

    public const PRODUCTS = 'products';

    public const ASSETS = 'assets';

    public const ORDERS = 'orders';

    public const DOCUMENTS = 'documents';

    protected static array $labels = [
        self::DASHBOARD => 'Dashboard',
        self::PROJECTS => 'Proyectos',
        self::TASKS => 'Tareas',
        self::PARTIES => 'Contactos',
        self::PRODUCTS => 'Productos',
        self::ASSETS => 'Activos',
        self::ORDERS => 'Órdenes',
        self::DOCUMENTS => 'Documentos',
    ];

    public static function all(): array
    {
        return array_keys(static::$labels);
    }

    public static function labels(): array
    {
        return static::$labels;
    }

    public static function label(?string $value, ?string $default = '—'): ?string
    {
        if ($value === null) {
            return $default;
        }

        return static::$labels[$value] ?? $default;
    }
}
