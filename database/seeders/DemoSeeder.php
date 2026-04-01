<?php

// database/seeders/DemoSeeder.php

namespace Database\Seeders;

use Database\Seeders\Modules\AppointmentModuleSeeder;
use Database\Seeders\Modules\AssetModuleSeeder;
use Database\Seeders\Modules\BaseModuleSeeder;
use Database\Seeders\Modules\BranchModuleSeeder;
use Database\Seeders\Modules\CrossRelationsSeeder;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private array $context = [];

    private array $enabledModules = [];

    public function __construct()
    {
        $this->enabledModules = config('seeders.modules.enabled', [
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
            CrossRelationsSeeder::class,
        ]);
    }

    public function run(): void
    {
        DB::transaction(function () {
            $this->loadModules();
        });
    }

    private function loadModules(): void
    {
        foreach ($this->enabledModules as $moduleClass) {
            if (! class_exists($moduleClass)) {
                $this->command->warn("Module {$moduleClass} not found, skipping...");

                continue;
            }

            /** @var BaseModuleSeeder $module */
            $module = app($moduleClass);
            $module->setContext($this->context);

            $this->command->info('Running: '.class_basename($moduleClass));
            $module->run();

            $this->context = array_merge($this->context, $module->getContext());
        }

        $this->command->info('Demo seeder completed successfully!');
    }
}
