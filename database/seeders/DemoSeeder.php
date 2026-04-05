<?php

// FILE: database/seeders/DemoSeeder.php | V2

namespace Database\Seeders;

use Database\Seeders\Modules\AppointmentModuleSeeder;
use Database\Seeders\Modules\AssetModuleSeeder;
use Database\Seeders\Modules\AttachmentDemoSeeder;
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
        $configuredEnabled = config('seeders.modules.enabled', []);
        $configuredDisabled = config('seeders.modules.disabled', []);

        $configuredEnabled = is_array($configuredEnabled) ? $configuredEnabled : [];
        $configuredDisabled = is_array($configuredDisabled) ? $configuredDisabled : [];

        $defaultModules = [
            TenantModuleSeeder::class,
            UserModuleSeeder::class,
            PermissionModuleSeeder::class,
            RoleModuleSeeder::class,
            MembershipModuleSeeder::class,
            BranchModuleSeeder::class,
            InvitationModuleSeeder::class,
            PartyModuleSeeder::class,
            ProjectModuleSeeder::class,
            ProductModuleSeeder::class,
            AssetModuleSeeder::class,
            TaskModuleSeeder::class,
            OrderModuleSeeder::class,
            DocumentModuleSeeder::class,
            AppointmentModuleSeeder::class,
            CrossRelationsSeeder::class,
            AttachmentDemoSeeder::class,
        ];

        $modules = ! empty($configuredEnabled) ? $configuredEnabled : $defaultModules;

        if (! in_array(CrossRelationsSeeder::class, $modules, true)) {
            $modules[] = CrossRelationsSeeder::class;
        }

        $this->enabledModules = array_values(array_filter(
            $modules,
            fn (string $moduleClass) => ! in_array($moduleClass, $configuredDisabled, true)
        ));
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->loadModules();
        });
    }

    private function loadModules(): void
    {
        foreach ($this->enabledModules as $moduleClass) {
            if (! class_exists($moduleClass)) {
                $this->command?->warn("Module {$moduleClass} not found, skipping...");

                continue;
            }

            /** @var BaseModuleSeeder $module */
            $module = app($moduleClass);
            $module->setContext($this->context);

            $this->command?->info('Running: '.class_basename($moduleClass));
            $module->run();

            $this->context = array_merge($this->context, $module->getContext());
        }

        $this->command?->info('Demo seeder completed successfully!');
    }
}
