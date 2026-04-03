<?php

// FILE: app/Support/Auth/RolePermissionMatrix.php | V2

namespace App\Support\Auth;

use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\RoleCatalog;

class RolePermissionMatrix
{
    public static function all(): array
    {
        return static::mergeRecursiveDistinct(
            static::defaults(),
            static::overrides()
        );
    }

    protected static function overrides(): array
    {
        $overrides = config('role_permission_matrix', []);

        return is_array($overrides) ? $overrides : [];
    }

    protected static function defaults(): array
    {
        return [
            ModuleCatalog::PROJECTS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'own_assigned',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'own_assigned',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'own_assigned',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::TASKS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'all',
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'all',
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::APPOINTMENTS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'all',
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'all',
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => 'own_assigned',
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::PARTIES => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::PRODUCTS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::ASSETS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::ORDERS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::DOCUMENTS => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view_any' => true,
                        'view' => true,
                        'create' => true,
                        'update' => true,
                        'delete' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],

            ModuleCatalog::DASHBOARD => [
                RoleCatalog::OWNER => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view' => true,
                        'view_analytics' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMIN => [
                    'module_access' => true,
                    'record_visibility' => 'tenant_all',
                    'actions' => [
                        'view' => true,
                        'view_analytics' => true,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::SALES => [
                    'module_access' => true,
                    'record_visibility' => 'limited',
                    'actions' => [
                        'view' => true,
                        'view_analytics' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::OPERATOR => [
                    'module_access' => true,
                    'record_visibility' => 'limited',
                    'actions' => [
                        'view' => true,
                        'view_analytics' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
                RoleCatalog::ADMINISTRATOR => [
                    'module_access' => true,
                    'record_visibility' => 'limited',
                    'actions' => [
                        'view' => true,
                        'view_analytics' => false,
                    ],
                    'type_restrictions' => ['*'],
                ],
            ],
        ];
    }

    public static function for(string $module, string $role): array
    {
        return static::all()[$module][$role] ?? static::emptyRule();
    }

    public static function emptyRule(): array
    {
        return [
            'module_access' => false,
            'record_visibility' => 'none',
            'actions' => [],
            'type_restrictions' => [],
        ];
    }

    protected static function mergeRecursiveDistinct(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (
                array_key_exists($key, $base)
                && is_array($base[$key])
                && is_array($value)
            ) {
                $base[$key] = static::mergeRecursiveDistinct($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
