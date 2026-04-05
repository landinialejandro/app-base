<?php

// FILE: database/seeders/Modules/CrossRelationsSeeder.php | V2

namespace Database\Seeders\Modules;

use Illuminate\Support\Facades\DB;

class CrossRelationsSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('tasks')
            || ! $this->hasDependency('orders')
            || ! $this->hasDependency('assets')
        ) {
            throw new \RuntimeException('CrossRelationsSeeder requires tenants, tasks, orders, and assets');
        }

        $tenants = $this->getDependency('tenants');
        $tasks = $this->getDependency('tasks');
        $orders = $this->getDependency('orders');
        $assets = $this->getDependency('assets');
        $documents = $this->getDependency('documents') ?? [];
        $appointments = $this->getDependency('appointments') ?? [];

        $this->linkTasksToOrders($tasks, $orders);
        $this->linkOrdersToAssets($orders, $assets);
        $this->linkDocumentsToAssets($documents, $assets);
        $this->linkAppointmentsToOrders($appointments, $orders);
        $this->verifyOrdersToTasks($tenants, $orders, $tasks);

        $this->command?->info('Cross relations created successfully!');
    }

    private function linkTasksToOrders($tasks, $orders): void
    {
        $this->linkTaskAndOrderPair(
            $tasks['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
            'Relevar usuarios operativos',
            'service'
        );

        $this->linkTaskAndOrderPair(
            $tasks['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
            'Reunión inicial con cliente',
            'sale'
        );

        $this->linkTaskAndOrderPair(
            $tasks['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
            'Reunión con dirección de obra',
            'sale'
        );

        $this->linkTaskAndOrderPair(
            $tasks['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
            'Revisar documentación estructural',
            'service'
        );
    }

    private function linkTaskAndOrderPair($tasks, $orders, string $taskName, string $orderKind): void
    {
        $task = $tasks->firstWhere('name', $taskName);
        $order = $orders->firstWhere('kind', $orderKind);

        if (! $task || ! $order) {
            return;
        }

        $order->update(['task_id' => $task->id]);

        $this->command?->info("Linked task '{$task->name}' to order '{$order->number}'");
    }

    private function linkOrdersToAssets($orders, $assets): void
    {
        $this->linkOrderAndAssetPair(
            $orders['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
            'service',
            'vehicle'
        );

        $this->linkOrderAndAssetPair(
            $orders['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
            'sale',
            'equipment'
        );

        $this->linkOrderAndAssetPair(
            $orders['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
            'sale',
            'machinery'
        );

        $this->linkOrderAndAssetPair(
            $orders['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
            'service',
            'tool'
        );
    }

    private function linkOrderAndAssetPair($orders, $assets, string $orderKind, string $assetKind): void
    {
        $order = $orders->firstWhere('kind', $orderKind);
        $asset = $assets->firstWhere('kind', $assetKind);

        if (! $order || ! $asset) {
            return;
        }

        $order->update(['asset_id' => $asset->id]);

        $this->command?->info("Linked order '{$order->number}' to asset '{$asset->name}'");
    }

    private function linkDocumentsToAssets($documents, $assets): void
    {
        $this->linkDocumentAndAssetPair(
            $documents['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
            'quote',
            'vehicle'
        );

        $this->linkDocumentAndAssetPair(
            $documents['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
            'delivery_note',
            'equipment'
        );

        $this->linkDocumentAndAssetPair(
            $documents['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
            'quote',
            'machinery'
        );

        $this->linkDocumentAndAssetPair(
            $documents['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
            'invoice',
            'tool'
        );
    }

    private function linkDocumentAndAssetPair($documents, $assets, string $documentKind, string $assetKind): void
    {
        $document = $documents->firstWhere('kind', $documentKind);
        $asset = $assets->firstWhere('kind', $assetKind);

        if (! $document || ! $asset) {
            return;
        }

        DB::table('documents')
            ->where('id', $document->id)
            ->update([
                'asset_id' => $asset->id,
                'updated_at' => now(),
            ]);

        $this->command?->info("Linked document '{$document->number}' to asset '{$asset->name}'");
    }

    private function linkAppointmentsToOrders($appointments, $orders): void
    {
        $this->linkAppointmentAndOrderPair(
            $appointments['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
            'service',
            'service'
        );

        $this->linkAppointmentAndOrderPair(
            $appointments['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
            'visit',
            'sale'
        );

        $this->linkAppointmentAndOrderPair(
            $appointments['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
            'service',
            'service'
        );

        $this->linkAppointmentAndOrderPair(
            $appointments['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
            'visit',
            'sale'
        );
    }

    private function linkAppointmentAndOrderPair($appointments, $orders, string $appointmentKind, string $orderKind): void
    {
        $appointment = $appointments->firstWhere('kind', $appointmentKind);
        $order = $orders->firstWhere('kind', $orderKind);

        if (! $appointment || ! $order) {
            return;
        }

        DB::table('appointments')
            ->where('id', $appointment->id)
            ->update([
                'order_id' => $order->id,
                'updated_at' => now(),
            ]);

        $this->command?->info("Linked appointment '{$appointment->title}' to order '{$order->number}'");
    }

    private function verifyOrdersToTasks($tenants, $orders, $tasks): void
    {
        foreach (['tech', 'andina'] as $tenantKey) {
            $tenantOrders = $orders[$tenantKey] ?? collect();
            $tenantTasks = $tasks[$tenantKey] ?? collect();

            $linkedOrders = $tenantOrders->filter(fn ($order) => ! empty($order->task_id))->count();
            $availableTasks = $tenantTasks->count();

            $this->command?->info(
                "Verified task/order relations for tenant '{$tenantKey}': {$linkedOrders} orders linked, {$availableTasks} tasks available."
            );
        }
    }
}
