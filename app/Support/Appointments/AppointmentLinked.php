<?php

// FILE: app/Support/Appointments/AppointmentLinked.php | V1

namespace App\Support\Appointments;

use App\Models\Appointment;
use App\Models\Order;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;

class AppointmentLinked
{
    public static function forOrder(Order $order, array $trailQuery = [], bool $allowCreate = true): array
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $supported = TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);

        if (! $supported || ! $user) {
            return [
                'supported' => $supported,
                'exists' => false,
                'hidden' => true,
                'readonly' => false,
                'state' => 'hidden',
                'show_url' => null,
                'create_url' => null,
                'label' => 'Turno',
                'text' => null,
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

        return [
            'supported' => true,
            'exists' => $linked,
            'hidden' => ! $linked && ! $canCreate,
            'readonly' => $linked && ! $canView,
            'state' => self::stateFor(
                linked: $linked,
                canView: (bool) $canView,
                canCreate: (bool) $canCreate,
            ),
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
            'text' => $linked
                ? ($appointment->title ?: 'Turno #'.$appointment->id)
                : null,
        ];
    }

    protected static function stateFor(bool $linked, bool $canView, bool $canCreate): string
    {
        if ($linked) {
            return $canView ? 'linked_viewable' : 'linked_readonly';
        }

        if ($canCreate) {
            return 'creatable';
        }

        return 'hidden';
    }
}
