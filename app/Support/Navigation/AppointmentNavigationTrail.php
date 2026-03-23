<?php

// FILE: app/Support/Navigation/AppointmentNavigationTrail.php | V4

namespace App\Support\Navigation;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentNavigationTrail
{
    public static function appointmentsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('appointments.index', null, 'Turnos', route('appointments.index')),
        ]);
    }

    public static function base(Appointment $appointment): array
    {
        $trail = self::appointmentsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'appointments.show',
                $appointment->id,
                $appointment->title ?: 'Turno #'.$appointment->id,
                route('appointments.show', ['appointment' => $appointment])
            )
        );
    }

    public static function create(): array
    {
        $trail = self::appointmentsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'appointments.create',
                'new',
                'Nuevo turno',
                route('appointments.create')
            )
        );
    }

    public static function show(Request $request, Appointment $appointment): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::appointmentsBase();
        }

        $trail = NavigationTrail::sliceBefore($trail, 'appointments.create', 'new');
        $trail = NavigationTrail::sliceBefore($trail, 'appointments.edit', $appointment->id);

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'appointments.show',
                $appointment->id,
                $appointment->title ?: 'Turno #'.$appointment->id,
                route('appointments.show', ['appointment' => $appointment])
            )
        );
    }

    public static function edit(Request $request, Appointment $appointment): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'appointments.show', $appointment->id)) {
            $trail = self::show($request, $appointment);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'appointments.edit',
                $appointment->id,
                'Editar',
                route('appointments.edit', ['appointment' => $appointment])
            )
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
