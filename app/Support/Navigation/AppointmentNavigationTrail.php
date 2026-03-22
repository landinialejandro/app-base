<?php

// FILE: app/Support/Navigation/AppointmentNavigationTrail.php | V1

namespace App\Support\Navigation;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentNavigationTrail
{
    public static function base(Appointment $appointment): array
    {
        $trail = NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('appointments.index', null, 'Turnos', route('appointments.index')),
            NavigationTrail::makeNode(
                'appointments.show',
                $appointment->id,
                $appointment->title ?: 'Turno #'.$appointment->id,
                route('appointments.show', ['appointment' => $appointment])
            ),
        ]);

        return NavigationTrail::replaceCurrentUrl(
            $trail,
            route('appointments.show', ['appointment' => $appointment] + NavigationTrail::toQuery($trail))
        );
    }

    public static function resolveFromRequest(Request $request, string $tenantId): ?Appointment
    {
        $appointmentId = $request->integer('appointment_id');

        if ($appointmentId <= 0) {
            return null;
        }

        return Appointment::query()
            ->where('id', $appointmentId)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first();
    }
}
