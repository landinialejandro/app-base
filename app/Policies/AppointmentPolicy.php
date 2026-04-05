<?php

// FILE: app/Policies/AppointmentPolicy.php | V5

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Auth\Security;

class AppointmentPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'appointments.viewAny');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->security()->allows($user, 'appointments.view', $appointment);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'appointments.create', Appointment::class);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->security()->allows($user, 'appointments.update', $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->security()->allows($user, 'appointments.delete', $appointment);
    }
}
