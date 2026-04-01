<?php

// database/seeders/Modules/PermissionModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Permission;

class PermissionModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $this->context['permissions'] = [];

        $permissionDefinitions = $this->getPermissionDefinitions();

        foreach ($permissionDefinitions as $definition) {
            $permission = Permission::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'group' => $definition['group'],
                    'description' => $definition['description'] ?? null,
                ]
            );

            $this->context['permissions'][$definition['slug']] = $permission;
        }
    }

    protected function getPermissionDefinitions(): array
    {
        return [
            // Projects
            ['slug' => 'projects.view', 'name' => 'Ver proyectos', 'group' => 'projects'],
            ['slug' => 'projects.create', 'name' => 'Crear proyectos', 'group' => 'projects'],
            ['slug' => 'projects.update', 'name' => 'Actualizar proyectos', 'group' => 'projects'],
            ['slug' => 'projects.delete', 'name' => 'Eliminar proyectos', 'group' => 'projects'],

            // Tasks
            ['slug' => 'tasks.view', 'name' => 'Ver tareas', 'group' => 'tasks'],
            ['slug' => 'tasks.create', 'name' => 'Crear tareas', 'group' => 'tasks'],
            ['slug' => 'tasks.update', 'name' => 'Actualizar tareas', 'group' => 'tasks'],
            ['slug' => 'tasks.delete', 'name' => 'Eliminar tareas', 'group' => 'tasks'],

            // Parties
            ['slug' => 'parties.view', 'name' => 'Ver partes', 'group' => 'parties'],
            ['slug' => 'parties.create', 'name' => 'Crear partes', 'group' => 'parties'],
            ['slug' => 'parties.update', 'name' => 'Actualizar partes', 'group' => 'parties'],
            ['slug' => 'parties.delete', 'name' => 'Eliminar partes', 'group' => 'parties'],

            // Products
            ['slug' => 'products.view', 'name' => 'Ver productos', 'group' => 'products'],
            ['slug' => 'products.create', 'name' => 'Crear productos', 'group' => 'products'],
            ['slug' => 'products.update', 'name' => 'Actualizar productos', 'group' => 'products'],
            ['slug' => 'products.delete', 'name' => 'Eliminar productos', 'group' => 'products'],

            // Orders
            ['slug' => 'orders.view', 'name' => 'Ver órdenes', 'group' => 'orders'],
            ['slug' => 'orders.create', 'name' => 'Crear órdenes', 'group' => 'orders'],
            ['slug' => 'orders.update', 'name' => 'Actualizar órdenes', 'group' => 'orders'],
            ['slug' => 'orders.delete', 'name' => 'Eliminar órdenes', 'group' => 'orders'],

            // Documents
            ['slug' => 'documents.view', 'name' => 'Ver documentos', 'group' => 'documents'],
            ['slug' => 'documents.create', 'name' => 'Crear documentos', 'group' => 'documents'],
            ['slug' => 'documents.update', 'name' => 'Actualizar documentos', 'group' => 'documents'],
            ['slug' => 'documents.delete', 'name' => 'Eliminar documentos', 'group' => 'documents'],

            // Assets
            ['slug' => 'assets.view', 'name' => 'Ver activos', 'group' => 'assets'],
            ['slug' => 'assets.create', 'name' => 'Crear activos', 'group' => 'assets'],
            ['slug' => 'assets.update', 'name' => 'Actualizar activos', 'group' => 'assets'],
            ['slug' => 'assets.delete', 'name' => 'Eliminar activos', 'group' => 'assets'],

            // Appointments
            ['slug' => 'appointments.view', 'name' => 'Ver turnos', 'group' => 'appointments'],
            ['slug' => 'appointments.create', 'name' => 'Crear turnos', 'group' => 'appointments'],
            ['slug' => 'appointments.update', 'name' => 'Actualizar turnos', 'group' => 'appointments'],
            ['slug' => 'appointments.delete', 'name' => 'Eliminar turnos', 'group' => 'appointments'],
        ];
    }
}
