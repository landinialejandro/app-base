<?php

// database/seeders/Modules/UserModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\User;

class UserModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $users = [];

        $users['superadmin'] = User::updateOrCreate(
            ['email' => 'superadmin@app-base.local'],
            ['name' => 'Super Admin', 'password' => 'password', 'is_superadmin' => true]
        );

        $users['ownerTech'] = User::firstOrCreate(
            ['email' => 'juan@tech.local'],
            ['name' => 'Juan Tech', 'password' => 'password', 'is_superadmin' => false]
        );

        $users['ownerAndina'] = User::firstOrCreate(
            ['email' => 'maria@andina.local'],
            ['name' => 'María Andina', 'password' => 'password', 'is_superadmin' => false]
        );

        $users['shared'] = User::firstOrCreate(
            ['email' => 'carlos@demo.local'],
            ['name' => 'Carlos Operaciones', 'password' => 'password', 'is_superadmin' => false]
        );

        $users['techUser'] = User::firstOrCreate(
            ['email' => 'ana@demo.local'],
            ['name' => 'Ana Comercial', 'password' => 'password', 'is_superadmin' => false]
        );

        $users['andinaUser'] = User::firstOrCreate(
            ['email' => 'pedro@demo.local'],
            ['name' => 'Pedro Obra', 'password' => 'password', 'is_superadmin' => false]
        );

        $this->context['users'] = $users;
    }
}
