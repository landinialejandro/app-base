<?php

// FILE: app/Support/Appointments/AppointmentSurfaceService.php | V3

namespace App\Support\Appointments;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class AppointmentSurfaceService implements ModuleSurfaceService
{
    public function offers(): array
    {
        return [];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }
}
