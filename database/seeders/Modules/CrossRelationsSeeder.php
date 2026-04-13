<?php

// FILE: database/seeders/Modules/CrossRelationsSeeder.php | V5

namespace Database\Seeders\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CrossRelationsSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tasks')
            || ! $this->hasDependency('orders')
            || ! $this->hasDependency('assets')
        ) {
            throw new \RuntimeException('CrossRelationsSeeder requires tasks, orders, and assets');
        }

        $tasks = $this->getDependency('tasks');
        $orders = $this->getDependency('orders');
        $assets = $this->getDependency('assets');
        $documents = $this->getDependency('documents') ?? [];
        $appointments = $this->getDependency('appointments') ?? [];

        $this->linkTechTaskOrderRelations(
            $tasks['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
        );

        $this->linkAndinaTaskOrderRelations(
            $tasks['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
        );

        $this->linkTechOrderAssetRelations(
            $orders['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
        );

        $this->linkAndinaOrderAssetRelations(
            $orders['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
        );

        $this->linkTechDocumentAssetRelations(
            $documents['tech'] ?? collect(),
            $assets['tech'] ?? collect(),
        );

        $this->linkAndinaDocumentAssetRelations(
            $documents['andina'] ?? collect(),
            $assets['andina'] ?? collect(),
        );

        $this->linkTechAppointmentOrderRelations(
            $appointments['tech'] ?? collect(),
            $orders['tech'] ?? collect(),
        );

        $this->linkAndinaAppointmentOrderRelations(
            $appointments['andina'] ?? collect(),
            $orders['andina'] ?? collect(),
        );

        $this->command?->info('Cross relations created successfully!');
    }

    private function linkTechTaskOrderRelations(Collection $tasks, Collection $orders): void
    {
        $this->linkTaskToOrderByIdentifiers(
            tasks: $tasks,
            orders: $orders,
            taskName: 'Relevar usuarios operativos',
            orderNumber: 'TECH-ORD-0002',
        );

        $this->linkTaskToOrderByIdentifiers(
            tasks: $tasks,
            orders: $orders,
            taskName: 'Reunión inicial con cliente',
            orderNumber: 'TECH-ORD-0001',
        );
    }

    private function linkAndinaTaskOrderRelations(Collection $tasks, Collection $orders): void
    {
        $this->linkTaskToOrderByIdentifiers(
            tasks: $tasks,
            orders: $orders,
            taskName: 'Reunión con dirección de obra',
            orderNumber: 'AND-ORD-0001',
        );

        $this->linkTaskToOrderByIdentifiers(
            tasks: $tasks,
            orders: $orders,
            taskName: 'Revisar documentación estructural',
            orderNumber: 'AND-ORD-0002',
        );
    }

    private function linkTechOrderAssetRelations(Collection $orders, Collection $assets): void
    {
        $this->linkOrderToAssetByIdentifiers(
            orders: $orders,
            assets: $assets,
            orderNumber: 'TECH-ORD-0001',
            assetInternalCode: 'TECH-VEH-001',
        );

        $this->linkOrderToAssetByIdentifiers(
            orders: $orders,
            assets: $assets,
            orderNumber: 'TECH-ORD-0002',
            assetInternalCode: 'TECH-EQP-001',
        );
    }

    private function linkAndinaOrderAssetRelations(Collection $orders, Collection $assets): void
    {
        $this->linkOrderToAssetByIdentifiers(
            orders: $orders,
            assets: $assets,
            orderNumber: 'AND-ORD-0001',
            assetInternalCode: 'AND-MAQ-001',
        );

        $this->linkOrderToAssetByIdentifiers(
            orders: $orders,
            assets: $assets,
            orderNumber: 'AND-ORD-0002',
            assetInternalCode: 'AND-TOL-001',
        );
    }

    private function linkTechDocumentAssetRelations(Collection $documents, Collection $assets): void
    {
        $this->linkDocumentToAssetByIdentifiers(
            documents: $documents,
            assets: $assets,
            documentNumber: 'PRE-00000001',
            assetInternalCode: 'TECH-VEH-001',
        );

        $this->linkDocumentToAssetByIdentifiers(
            documents: $documents,
            assets: $assets,
            documentNumber: 'REM-00000001',
            assetInternalCode: 'TECH-EQP-001',
        );
    }

    private function linkAndinaDocumentAssetRelations(Collection $documents, Collection $assets): void
    {
        $this->linkDocumentToAssetByIdentifiers(
            documents: $documents,
            assets: $assets,
            documentNumber: 'PRE-00000002',
            assetInternalCode: 'AND-MAQ-001',
        );

        $this->linkDocumentToAssetByIdentifiers(
            documents: $documents,
            assets: $assets,
            documentNumber: 'FAC-00000001',
            assetInternalCode: 'AND-TOL-001',
        );
    }

    private function linkTechAppointmentOrderRelations(Collection $appointments, Collection $orders): void
    {
        $this->linkAppointmentToOrderByIdentifiers(
            appointments: $appointments,
            orders: $orders,
            appointmentTitle: 'Servicio programado',
            orderNumber: 'TECH-ORD-0002',
        );

        $this->linkAppointmentToOrderByIdentifiers(
            appointments: $appointments,
            orders: $orders,
            appointmentTitle: 'Visita técnica inicial',
            orderNumber: 'TECH-ORD-0001',
        );
    }

    private function linkAndinaAppointmentOrderRelations(Collection $appointments, Collection $orders): void
    {
        $this->linkAppointmentToOrderByIdentifiers(
            appointments: $appointments,
            orders: $orders,
            appointmentTitle: 'Servicio de inspección',
            orderNumber: 'AND-ORD-0002',
        );

        $this->linkAppointmentToOrderByIdentifiers(
            appointments: $appointments,
            orders: $orders,
            appointmentTitle: 'Visita a obra',
            orderNumber: 'AND-ORD-0001',
        );
    }

    private function linkTaskToOrderByIdentifiers(
        Collection $tasks,
        Collection $orders,
        string $taskName,
        string $orderNumber
    ): void {
        $task = $this->firstOrFailBy($tasks, 'name', $taskName, 'task');
        $order = $this->firstOrFailBy($orders, 'number', $orderNumber, 'order');

        $order->update([
            'task_id' => $task->id,
        ]);

        $this->command?->info("Linked task '{$task->name}' to order '{$order->number}'");
    }

    private function linkOrderToAssetByIdentifiers(
        Collection $orders,
        Collection $assets,
        string $orderNumber,
        string $assetInternalCode
    ): void {
        $order = $this->firstOrFailBy($orders, 'number', $orderNumber, 'order');
        $asset = $this->firstOrFailBy($assets, 'internal_code', $assetInternalCode, 'asset');

        if (
            $order->party_id !== null
            && $asset->party_id !== null
            && (int) $order->party_id !== (int) $asset->party_id
        ) {
            throw new \RuntimeException(
                "CrossRelationsSeeder mismatch: order '{$order->number}' and asset '{$asset->internal_code}' do not share the same party_id."
            );
        }

        $order->update([
            'asset_id' => $asset->id,
            'party_id' => $asset->party_id ?: $order->party_id,
        ]);

        $this->command?->info("Linked order '{$order->number}' to asset '{$asset->name}'");
    }

    private function linkDocumentToAssetByIdentifiers(
        Collection $documents,
        Collection $assets,
        string $documentNumber,
        string $assetInternalCode
    ): void {
        $document = $this->firstOrFailBy($documents, 'number', $documentNumber, 'document');
        $asset = $this->firstOrFailBy($assets, 'internal_code', $assetInternalCode, 'asset');

        if (
            $document->party_id !== null
            && $asset->party_id !== null
            && (int) $document->party_id !== (int) $asset->party_id
        ) {
            throw new \RuntimeException(
                "CrossRelationsSeeder mismatch: document '{$document->number}' and asset '{$asset->internal_code}' do not share the same party_id."
            );
        }

        DB::table('documents')
            ->where('id', $document->id)
            ->update([
                'asset_id' => $asset->id,
                'party_id' => $asset->party_id ?: $document->party_id,
                'updated_at' => now(),
            ]);

        $this->command?->info("Linked document '{$document->number}' to asset '{$asset->name}'");
    }

    private function linkAppointmentToOrderByIdentifiers(
        Collection $appointments,
        Collection $orders,
        string $appointmentTitle,
        string $orderNumber
    ): void {
        $appointment = $this->firstOrFailBy($appointments, 'title', $appointmentTitle, 'appointment');
        $order = $this->firstOrFailBy($orders, 'number', $orderNumber, 'order');

        if (
            $appointment->party_id !== null
            && $order->party_id !== null
            && (int) $appointment->party_id !== (int) $order->party_id
        ) {
            throw new \RuntimeException(
                "CrossRelationsSeeder mismatch: appointment '{$appointment->title}' and order '{$order->number}' do not share the same party_id."
            );
        }

        if (
            $appointment->asset_id !== null
            && $order->asset_id !== null
            && (int) $appointment->asset_id !== (int) $order->asset_id
        ) {
            throw new \RuntimeException(
                "CrossRelationsSeeder mismatch: appointment '{$appointment->title}' and order '{$order->number}' do not share the same asset_id."
            );
        }

        DB::table('appointments')
            ->where('id', $appointment->id)
            ->update([
                'order_id' => $order->id,
                'updated_at' => now(),
            ]);

        $this->command?->info("Linked appointment '{$appointment->title}' to order '{$order->number}'");
    }

    private function firstOrFailBy(Collection $collection, string $field, mixed $value, string $entityLabel): object
    {
        $record = $collection->firstWhere($field, $value);

        if (! $record) {
            throw new \RuntimeException(
                "CrossRelationsSeeder could not find {$entityLabel} by {$field}='{$value}'."
            );
        }

        return $record;
    }
}
