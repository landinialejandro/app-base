<?php

// FILE: database/seeders/Modules/UserModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\User;

class UserModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $users = [];

        $users['superadmin'] = User::updateOrCreate(
            ['email' => 'superadmin@app-base.local'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_superadmin' => true,
            ]
        );

        $users['ownerTech'] = User::updateOrCreate(
            ['email' => 'juan@tech.local'],
            [
                'name' => 'Juan Tech',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['ownerAndina'] = User::updateOrCreate(
            ['email' => 'maria@andina.local'],
            [
                'name' => 'María Andina',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['shared'] = User::updateOrCreate(
            ['email' => 'carlos@demo.local'],
            [
                'name' => 'Carlos Demo',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['techUser'] = User::updateOrCreate(
            ['email' => 'ana@demo.local'],
            [
                'name' => 'Ana Tech',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['andinaUser'] = User::updateOrCreate(
            ['email' => 'pedro@demo.local'],
            [
                'name' => 'Pedro Andina',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['lavaderoOwner'] = User::updateOrCreate(
            ['email' => 'santiago.mendez@lavaderosa.local'],
            [
                'name' => 'Santiago Méndez',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['lavaderoAdmin'] = User::updateOrCreate(
            ['email' => 'laura.ferreyra@lavaderosa.local'],
            [
                'name' => 'Laura Ferreyra',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['lavaderoSales'] = User::updateOrCreate(
            ['email' => 'martin.aguirre@lavaderosa.local'],
            [
                'name' => 'Martín Aguirre',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $users['lavaderoAdministrator'] = User::updateOrCreate(
            ['email' => 'carolina.torres@lavaderosa.local'],
            [
                'name' => 'Carolina Torres',
                'password' => 'password',
                'is_superadmin' => false,
            ]
        );

        $this->context['users'] = $users;
    }
}