<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [

    'models' => [
        'permission' => Permission::class,
        'role'       => Role::class,
    ],

    'table_names' => [
        'roles'                 => 'roles',
        'permissions'           => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles'       => 'model_has_roles',
        'role_has_permissions'  => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key'       => null,
        'permission_pivot_key' => null,

        // BIGINT user IDs — kein UUID nötig
        'model_morph_key'      => 'model_id',

        // Team-Spalte = tenant_id (BIGINT)
        'team_foreign_key'     => 'tenant_id',
    ],

    // Teams-Modus: Permissions sind mandanten-gebunden
    // Super-Admin nutzt Team-ID 0 (globales Team)
    'teams' => true,

    'register_permission_check_method' => true,
    'register_octane_reset_listener'   => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key'             => 'spatie.permission.cache',
        'store'           => 'default',
    ],
];
