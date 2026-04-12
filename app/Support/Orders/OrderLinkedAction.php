<?php

// FILE: app/Support/Orders/OrderLinkedAction.php | V1

namespace App\Support\Orders;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\Task;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;

class OrderLinkedAction
{
    public static function forAppointment(Appointment $appointment, array $trailQuery = [], bool $allowCreate = true): array
    {
        $supported = self::supportsOrders();
        $user = auth()->user();

        $linked = (bool) $appointment->order;
        $canView = $supported && $linked && $user && $user->can('view', $appointment->order);
        $canCreate = $supported && $allowCreate && self::canCreateOrders();

        return [
            'supported' => $supported,
            'linked' => $linked,
            'can_view' => $canView,
            'can_create' => $canCreate,
            'show_url' => $linked
                ? route('orders.show', ['order' => $appointment->order] + $trailQuery)
                : null,
            'create_url' => $supported
                ? route('orders.create', [
                    'appointment_id' => $appointment->id,
                    'party_id' => $appointment->party_id,
                    'asset_id' => $appointment->asset_id,
                ] + $trailQuery)
                : null,
            'label' => AppointmentCatalog::orderLabel(),
            'contact_label' => AppointmentCatalog::contactLabel(),
            'has_required_party' => (bool) $appointment->party_id,
            'linked_text' => $linked
                ? ($appointment->order->number ?: 'Orden #'.$appointment->order->id)
                : null,
        ];
    }

    public static function forTask(Task $task, array $trailQuery = [], bool $allowCreate = true): array
    {
        $supported = self::supportsOrders();
        $user = auth()->user();

        $linked = (bool) $task->order;
        $canView = $supported && $linked && $user && $user->can('view', $task->order);
        $canCreate = $supported && $allowCreate && self::canCreateOrders();

        return [
            'supported' => $supported,
            'linked' => $linked,
            'can_view' => $canView,
            'can_create' => $canCreate,
            'show_url' => $linked
                ? route('orders.show', ['order' => $task->order] + $trailQuery)
                : null,
            'create_url' => $supported
                ? route('orders.create', ['task_id' => $task->id] + $trailQuery)
                : null,
            'label' => 'Orden',
            'contact_label' => 'Contacto',
            'has_required_party' => (bool) $task->party_id,
            'linked_text' => $linked
                ? ($task->order->number ?: 'Ver orden')
                : null,
        ];
    }

    protected static function supportsOrders(): bool
    {
        return TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, app('tenant'));
    }

    protected static function canCreateOrders(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return collect(OrderCatalog::kinds())->contains(
            fn (string $kind) => app(Security::class)->allows(
                $user,
                'orders.create',
                Order::class,
                ['kind' => $kind]
            )
        );
    }
}
