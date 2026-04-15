<?php

// FILE: app/Support/Appointments/AppointmentLinkedAction.php | V2

namespace App\Support\Appointments;

use App\Models\Appointment;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;

class AppointmentLinkedAction
{
    public static function forOrder(Order $order, array $trailQuery = [], bool $allowCreate = true): array
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $supported = TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);

        if (! $supported || ! $user) {
            return [
                'supported' => $supported,
                'linked' => false,
                'can_view' => false,
                'can_create' => false,
                'readonly' => false,
                'hidden' => true,
                'show_url' => null,
                'create_url' => null,
                'label' => 'Turno',
                'linked_text' => null,
            ];
        }

        $appointment = app(Security::class)
            ->scope($user, 'appointments.viewAny', Appointment::query())
            ->where('order_id', $order->id)
            ->where('tenant_id', $order->tenant_id)
            ->whereNull('deleted_at')
            ->first();

        $linked = $appointment instanceof Appointment;
        $canView = $linked && $user->can('view', $appointment);
        $canCreate = ! $linked && $allowCreate && $user->can('create', Appointment::class);

        $readonly = $linked && ! $canView;
        $hidden = ! $linked && ! $canCreate;

        return [
            'supported' => true,
            'linked' => $linked,
            'can_view' => (bool) $canView,
            'can_create' => (bool) $canCreate,
            'readonly' => $readonly,
            'hidden' => $hidden,
            'show_url' => ($linked && $canView)
                ? route('appointments.show', ['appointment' => $appointment] + $trailQuery)
                : null,
            'create_url' => $canCreate
                ? route('appointments.create', [
                    'order_id' => $order->id,
                    'party_id' => $order->party_id,
                    'asset_id' => $order->asset_id,
                ] + $trailQuery)
                : null,
            'label' => 'Turno',
            'linked_text' => $linked
                ? ($appointment->title ?: 'Turno #'.$appointment->id)
                : null,
        ];
    }
}
