<?php

// FILE: app/Support/Orders/OrderLinked.php | V1

namespace App\Support\Orders;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\Task;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;

class OrderLinked
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
            'exists' => $linked,
            'hidden' => ! $supported,
            'readonly' => $linked && ! $canView,
            'state' => self::stateFor(
                supported: $supported,
                linked: $linked,
                canView: (bool) $canView,
                canCreate: $canCreate,
                hasRequiredParty: (bool) $appointment->party_id,
            ),
            'show_url' => $linked && $canView
                ? route('orders.show', ['order' => $appointment->order] + $trailQuery)
                : null,
            'create_url' => $supported && $canCreate
                ? route('orders.create', [
                    'appointment_id' => $appointment->id,
                    'party_id' => $appointment->party_id,
                    'asset_id' => $appointment->asset_id,
                ] + $trailQuery)
                : null,
            'label' => AppointmentCatalog::orderLabel(),
            'contact_label' => AppointmentCatalog::contactLabel(),
            'has_required_party' => (bool) $appointment->party_id,
            'text' => $linked
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
            'exists' => $linked,
            'hidden' => ! $supported,
            'readonly' => $linked && ! $canView,
            'state' => self::stateFor(
                supported: $supported,
                linked: $linked,
                canView: (bool) $canView,
                canCreate: $canCreate,
                hasRequiredParty: (bool) $task->party_id,
            ),
            'show_url' => $linked && $canView
                ? route('orders.show', ['order' => $task->order] + $trailQuery)
                : null,
            'create_url' => $supported && $canCreate
                ? route('orders.create', ['task_id' => $task->id] + $trailQuery)
                : null,
            'label' => 'Orden',
            'contact_label' => 'Contacto',
            'has_required_party' => (bool) $task->party_id,
            'text' => $linked
                ? ($task->order->number ?: 'Ver orden')
                : null,
        ];
    }

    public static function forOrder(?Order $order, array $trailQuery = [], string $label = 'Orden asociada'): array
    {
        if (! $order) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'create_url' => null,
                'label' => $label,
                'contact_label' => 'Contacto',
                'has_required_party' => false,
                'text' => '—',
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $order));

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView
                ? route('orders.show', ['order' => $order] + $trailQuery)
                : null,
            'create_url' => null,
            'label' => $label,
            'contact_label' => 'Contacto',
            'has_required_party' => false,
            'text' => $order->number ?: 'Orden #'.$order->id,
        ];
    }

    protected static function stateFor(
        bool $supported,
        bool $linked,
        bool $canView,
        bool $canCreate,
        bool $hasRequiredParty,
    ): string {
        if (! $supported) {
            return 'hidden';
        }

        if ($linked) {
            return $canView ? 'linked_viewable' : 'linked_readonly';
        }

        if ($hasRequiredParty && $canCreate) {
            return 'creatable';
        }

        if (! $hasRequiredParty) {
            return 'missing_requirement';
        }

        return 'hidden';
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
