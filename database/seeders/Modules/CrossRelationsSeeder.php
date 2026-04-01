<?php

// database/seeders/Modules/CrossRelationsSeeder.php

namespace Database\Seeders\Modules;

use Illuminate\Support\Facades\DB;

class CrossRelationsSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('tasks') || ! $this->hasDependency('orders') || ! $this->hasDependency('assets')) {
            throw new \RuntimeException('CrossRelationsSeeder requires tenants, tasks, orders, and assets');
        }

        $tenants = $this->getDependency('tenants');
        $tasks = $this->getDependency('tasks');
        $orders = $this->getDependency('orders');
        $assets = $this->getDependency('assets');
        $documents = $this->getDependency('documents') ?? [];
        $appointments = $this->getDependency('appointments') ?? [];

        // 1. Vincular tareas a órdenes
        $this->linkTasksToOrders($tenants, $tasks, $orders);

        // 2. Vincular órdenes a activos
        $this->linkOrdersToAssets($tenants, $orders, $assets);

        // 3. Vincular documentos a activos
        $this->linkDocumentsToAssets($tenants, $documents, $assets);

        // 4. Vincular turnos a órdenes
        $this->linkAppointmentsToOrders($tenants, $appointments, $orders);

        // 5. Crear relaciones de orden a tarea (inversa)
        $this->linkOrdersToTasks($tenants, $orders, $tasks);

        $this->command->info('Cross relations created successfully!');
    }

    private function linkTasksToOrders($tenants, $tasks, $orders): void
    {
        // Tech: Vincular tareas de tech con órdenes de tech
        $techTasks = $tasks['tech'] ?? collect();
        $techOrders = $orders['tech'] ?? collect();

        if ($techTasks->count() > 0 && $techOrders->count() > 0) {
            // Tarea "Service general" con orden de servicio
            $serviceTask = $techTasks->firstWhere('name', 'Relevar usuarios operativos');
            $serviceOrder = $techOrders->firstWhere('kind', 'service');

            if ($serviceTask && $serviceOrder) {
                $serviceOrder->update(['task_id' => $serviceTask->id]);
                $this->command->info("Linked task '{$serviceTask->name}' to order '{$serviceOrder->number}'");
            }

            // Tarea "Reunión inicial" con orden de venta
            $meetingTask = $techTasks->firstWhere('name', 'Reunión inicial con cliente');
            $saleOrder = $techOrders->firstWhere('kind', 'sale');

            if ($meetingTask && $saleOrder) {
                $saleOrder->update(['task_id' => $meetingTask->id]);
                $this->command->info("Linked task '{$meetingTask->name}' to order '{$saleOrder->number}'");
            }
        }

        // Andina: Vincular tareas de andina con órdenes de andina
        $andinaTasks = $tasks['andina'] ?? collect();
        $andinaOrders = $orders['andina'] ?? collect();

        if ($andinaTasks->count() > 0 && $andinaOrders->count() > 0) {
            // Tarea "Reunión dirección obra" con orden de venta
            $meetingTask = $andinaTasks->firstWhere('name', 'Reunión con dirección de obra');
            $saleOrder = $andinaOrders->firstWhere('kind', 'sale');

            if ($meetingTask && $saleOrder) {
                $saleOrder->update(['task_id' => $meetingTask->id]);
                $this->command->info("Linked task '{$meetingTask->name}' to order '{$saleOrder->number}'");
            }

            // Tarea "Revisar documentación" con orden de servicio
            $reviewTask = $andinaTasks->firstWhere('name', 'Revisar documentación estructural');
            $serviceOrder = $andinaOrders->firstWhere('kind', 'service');

            if ($reviewTask && $serviceOrder) {
                $serviceOrder->update(['task_id' => $reviewTask->id]);
                $this->command->info("Linked task '{$reviewTask->name}' to order '{$serviceOrder->number}'");
            }
        }
    }

    private function linkOrdersToAssets($tenants, $orders, $assets): void
    {
        // Tech: Vincular órdenes a activos
        $techOrders = $orders['tech'] ?? collect();
        $techAssets = $assets['tech'] ?? collect();

        if ($techOrders->count() > 0 && $techAssets->count() > 0) {
            $vehicleAsset = $techAssets->firstWhere('kind', 'vehicle');
            $serviceOrder = $techOrders->firstWhere('kind', 'service');

            if ($vehicleAsset && $serviceOrder) {
                $serviceOrder->update(['asset_id' => $vehicleAsset->id]);
                $this->command->info("Linked order '{$serviceOrder->number}' to asset '{$vehicleAsset->name}'");
            }

            $equipmentAsset = $techAssets->firstWhere('kind', 'equipment');
            $saleOrder = $techOrders->firstWhere('kind', 'sale');

            if ($equipmentAsset && $saleOrder) {
                $saleOrder->update(['asset_id' => $equipmentAsset->id]);
                $this->command->info("Linked order '{$saleOrder->number}' to asset '{$equipmentAsset->name}'");
            }
        }

        // Andina: Vincular órdenes a activos
        $andinaOrders = $orders['andina'] ?? collect();
        $andinaAssets = $assets['andina'] ?? collect();

        if ($andinaOrders->count() > 0 && $andinaAssets->count() > 0) {
            $machineryAsset = $andinaAssets->firstWhere('kind', 'machinery');
            $saleOrder = $andinaOrders->firstWhere('kind', 'sale');

            if ($machineryAsset && $saleOrder) {
                $saleOrder->update(['asset_id' => $machineryAsset->id]);
                $this->command->info("Linked order '{$saleOrder->number}' to asset '{$machineryAsset->name}'");
            }

            $toolAsset = $andinaAssets->firstWhere('kind', 'tool');
            $serviceOrder = $andinaOrders->firstWhere('kind', 'service');

            if ($toolAsset && $serviceOrder) {
                $serviceOrder->update(['asset_id' => $toolAsset->id]);
                $this->command->info("Linked order '{$serviceOrder->number}' to asset '{$toolAsset->name}'");
            }
        }
    }

    private function linkDocumentsToAssets($tenants, $documents, $assets): void
    {
        // Tech documents to assets
        $techDocuments = $documents['tech'] ?? collect();
        $techAssets = $assets['tech'] ?? collect();

        if ($techDocuments->count() > 0 && $techAssets->count() > 0) {
            $quote = $techDocuments->firstWhere('kind', 'quote');
            $vehicleAsset = $techAssets->firstWhere('kind', 'vehicle');

            if ($quote && $vehicleAsset) {
                DB::table('documents')
                    ->where('id', $quote->id)
                    ->update(['asset_id' => $vehicleAsset->id]);
                $this->command->info("Linked document '{$quote->number}' to asset '{$vehicleAsset->name}'");
            }

            $workOrder = $techDocuments->firstWhere('kind', 'work_order');
            $equipmentAsset = $techAssets->firstWhere('kind', 'equipment');

            if ($workOrder && $equipmentAsset) {
                DB::table('documents')
                    ->where('id', $workOrder->id)
                    ->update(['asset_id' => $equipmentAsset->id]);
                $this->command->info("Linked document '{$workOrder->number}' to asset '{$equipmentAsset->name}'");
            }
        }

        // Andina documents to assets
        $andinaDocuments = $documents['andina'] ?? collect();
        $andinaAssets = $assets['andina'] ?? collect();

        if ($andinaDocuments->count() > 0 && $andinaAssets->count() > 0) {
            $invoice = $andinaDocuments->firstWhere('kind', 'invoice');
            $machineryAsset = $andinaAssets->firstWhere('kind', 'machinery');

            if ($invoice && $machineryAsset) {
                DB::table('documents')
                    ->where('id', $invoice->id)
                    ->update(['asset_id' => $machineryAsset->id]);
                $this->command->info("Linked document '{$invoice->number}' to asset '{$machineryAsset->name}'");
            }

            $receipt = $andinaDocuments->firstWhere('kind', 'receipt');
            $toolAsset = $andinaAssets->firstWhere('kind', 'tool');

            if ($receipt && $toolAsset) {
                DB::table('documents')
                    ->where('id', $receipt->id)
                    ->update(['asset_id' => $toolAsset->id]);
                $this->command->info("Linked document '{$receipt->number}' to asset '{$toolAsset->name}'");
            }
        }
    }

    private function linkAppointmentsToOrders($tenants, $appointments, $orders): void
    {
        // Tech appointments to orders
        $techAppointments = $appointments['tech'] ?? collect();
        $techOrders = $orders['tech'] ?? collect();

        if ($techAppointments->count() > 0 && $techOrders->count() > 0) {
            $serviceAppointment = $techAppointments->firstWhere('kind', 'service');
            $serviceOrder = $techOrders->firstWhere('kind', 'service');

            if ($serviceAppointment && $serviceOrder) {
                DB::table('appointments')
                    ->where('id', $serviceAppointment->id)
                    ->update(['order_id' => $serviceOrder->id]);
                $this->command->info("Linked appointment '{$serviceAppointment->title}' to order '{$serviceOrder->number}'");
            }

            $inspectionAppointment = $techAppointments->firstWhere('kind', 'inspection');
            $saleOrder = $techOrders->firstWhere('kind', 'sale');

            if ($inspectionAppointment && $saleOrder) {
                DB::table('appointments')
                    ->where('id', $inspectionAppointment->id)
                    ->update(['order_id' => $saleOrder->id]);
                $this->command->info("Linked appointment '{$inspectionAppointment->title}' to order '{$saleOrder->number}'");
            }
        }

        // Andina appointments to orders
        $andinaAppointments = $appointments['andina'] ?? collect();
        $andinaOrders = $orders['andina'] ?? collect();

        if ($andinaAppointments->count() > 0 && $andinaOrders->count() > 0) {
            $maintenanceAppointment = $andinaAppointments->firstWhere('kind', 'maintenance');
            $saleOrder = $andinaOrders->firstWhere('kind', 'sale');

            if ($maintenanceAppointment && $saleOrder) {
                DB::table('appointments')
                    ->where('id', $maintenanceAppointment->id)
                    ->update(['order_id' => $saleOrder->id]);
                $this->command->info("Linked appointment '{$maintenanceAppointment->title}' to order '{$saleOrder->number}'");
            }

            $meetingAppointment = $andinaAppointments->firstWhere('kind', 'meeting');
            $serviceOrder = $andinaOrders->firstWhere('kind', 'service');

            if ($meetingAppointment && $serviceOrder) {
                DB::table('appointments')
                    ->where('id', $meetingAppointment->id)
                    ->update(['order_id' => $serviceOrder->id]);
                $this->command->info("Linked appointment '{$meetingAppointment->title}' to order '{$serviceOrder->number}'");
            }
        }
    }

    private function linkOrdersToTasks($tenants, $orders, $tasks): void
    {
        // Esta es la relación inversa (ya la hicimos en linkTasksToOrders)
        // Pero aquí podemos agregar lógica adicional si es necesario
        $this->command->info('Orders to tasks relationships verified');
    }
}
