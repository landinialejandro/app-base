<?php

// FILE: app/Support/Navigation/NavigationContext.php | V1

namespace App\Support\Navigation;

use App\Models\Appointment;
use Illuminate\Http\Request;

class NavigationContext
{
    public static function resolveFromRequest(Request $request, string $tenantId): ?array
    {
        $contextType = (string) $request->get('context_type', '');
        $contextId = $request->integer('context_id');

        if ($contextType === 'appointment' && $contextId > 0) {
            $appointment = Appointment::query()
                ->where('id', $contextId)
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->first();

            if (! $appointment) {
                return null;
            }

            return self::makeAppointment($appointment);
        }

        return null;
    }

    public static function makeAppointment(Appointment $appointment): array
    {
        return [
            'type' => 'appointment',
            'id' => $appointment->id,
            'label' => $appointment->title ?: 'Turno #'.$appointment->id,
            'url' => route('appointments.show', $appointment),
        ];
    }

    public static function routeParams(?array $navigationContext): array
    {
        if (! $navigationContext) {
            return [];
        }

        return [
            'context_type' => $navigationContext['type'],
            'context_id' => $navigationContext['id'],
        ];
    }
}
