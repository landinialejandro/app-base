<?php

use Database\Seeders\Modules\AppointmentModuleSeeder;
use Database\Seeders\Modules\AssetModuleSeeder;
use Database\Seeders\Modules\BranchModuleSeeder;
use Database\Seeders\Modules\DocumentModuleSeeder;
use Database\Seeders\Modules\InvitationModuleSeeder;
use Database\Seeders\Modules\MembershipModuleSeeder;
use Database\Seeders\Modules\OrderModuleSeeder;
use Database\Seeders\Modules\PartyModuleSeeder;
use Database\Seeders\Modules\PermissionModuleSeeder;
use Database\Seeders\Modules\ProductModuleSeeder;
use Database\Seeders\Modules\ProjectModuleSeeder;
use Database\Seeders\Modules\RoleModuleSeeder;
use Database\Seeders\Modules\TaskModuleSeeder;
use Database\Seeders\Modules\TenantModuleSeeder;
use Database\Seeders\Modules\UserModuleSeeder;

// config/seeders.php

return [
    'modules' => [
        'enabled' => [
            TenantModuleSeeder::class,
            UserModuleSeeder::class,
            PermissionModuleSeeder::class,
            MembershipModuleSeeder::class,
            BranchModuleSeeder::class,
            RoleModuleSeeder::class,
            InvitationModuleSeeder::class,
            PartyModuleSeeder::class,
            ProjectModuleSeeder::class,
            TaskModuleSeeder::class,
            ProductModuleSeeder::class,
            OrderModuleSeeder::class,
            DocumentModuleSeeder::class,
            AssetModuleSeeder::class,
            AppointmentModuleSeeder::class,
        ],
        'disabled' => [],
    ],
    'demo' => [
        'tech' => [
            'target_parties' => 12,
            'target_projects' => 7,
            'target_tasks' => 20,
            'target_appointments' => 8,
        ],
        'andina' => [
            'target_parties' => 10,
            'target_projects' => 6,
            'target_tasks' => 16,
            'target_appointments' => 6,
        ],
    ],
];
