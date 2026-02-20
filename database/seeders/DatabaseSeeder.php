<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // 1. Crear SUPERADMIN
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => Hash::make('admin123'),
            'is_platform_admin' => true,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // 2. Crear ADMIN de la organización
        $admin = User::create([
            'name' => 'Admin Demo',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // 3. Crear ORGANIZACIÓN DE EJEMPLO
        $org = Organization::create([
            'name' => 'Empresa Demo',
            'slug' => Str::slug('Empresa Demo'),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $admin->organization_id = $org->id;
        $admin->save();

        // 4. Crear SUPERVISOR
        $supervisor = User::create([
            'name' => 'Supervisor Demo',
            'email' => 'supervisor@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'supervisor',
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // 5. Crear USUARIO NORMAL
        $user = User::create([
            'name' => 'Usuario Demo',
            'email' => 'user@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'user',
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        // 6. Crear USUARIO PENDIENTE DE APROBACIÓN
        $pending = User::create([
            'name' => 'Pendiente Demo',
            'email' => 'pendiente@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'user',
            'approved_at' => null,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Datos de prueba creados:');
        $this->command->info('   Superadmin: super@admin.com / admin123');
        $this->command->info('   Admin: admin@demo.com / password');
        $this->command->info('   Supervisor: supervisor@demo.com / password');
        $this->command->info('   Usuario: user@demo.com / password');
        $this->command->info('   Pendiente: pendiente@demo.com / password');
    }
}
