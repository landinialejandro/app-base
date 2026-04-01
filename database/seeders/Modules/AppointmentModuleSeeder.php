<?php

// database/seeders/Modules/AppointmentModuleSeeder.php

namespace Database\Seeders\Modules;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class AppointmentModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants') || ! $this->hasDependency('users') || ! $this->hasDependency('parties')) {
            throw new \RuntimeException('AppointmentModuleSeeder requires tenants, users, and parties');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $assets = $this->getDependency('assets');
        $appointments = [];

        // Tech appointments
        $techUsers = collect([$users['ownerTech'], $users['shared'], $users['techUser']]);
        $techParties = $parties['techFixed']->merge($parties['techExtra']);
        $techAssets = $assets['tech'] ?? collect();

        $appointments['tech'] = $this->createAppointments(
            $tenants['tech']->id,
            $techUsers,
            $techParties,
            $techAssets,
            8
        );

        // Andina appointments
        $andinaUsers = collect([$users['ownerAndina'], $users['shared'], $users['andinaUser']]);
        $andinaParties = $parties['andinaFixed']->merge($parties['andinaExtra']);
        $andinaAssets = $assets['andina'] ?? collect();

        $appointments['andina'] = $this->createAppointments(
            $tenants['andina']->id,
            $andinaUsers,
            $andinaParties,
            $andinaAssets,
            6
        );

        $this->context['appointments'] = $appointments;
    }

    private function createAppointments(
        string $tenantId,
        Collection $users,
        Collection $parties,
        Collection $assets,
        int $count
    ): Collection {
        $appointments = collect();

        for ($i = 0; $i < $count; $i++) {
            $user = $users->random();
            $party = fake()->boolean(70) ? $parties->random() : null;
            $asset = fake()->boolean(40) ? $assets->random() : null;
            $date = fake()->dateTimeBetween('-5 days', '+15 days');

            $appointment = Appointment::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'title' => fake()->sentence(3),
                    'scheduled_date' => $date->format('Y-m-d'),
                ],
                [
                    'party_id' => $party?->id,
                    'asset_id' => $asset?->id,
                    'assigned_user_id' => $user->id,
                    'kind' => fake()->randomElement(['service', 'maintenance', 'inspection', 'meeting']),
                    'status' => fake()->randomElement(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled']),
                    'work_mode' => fake()->randomElement(['presential', 'remote', 'hybrid']),
                    'notes' => fake()->optional()->paragraph(),
                    'workstation_name' => fake()->optional()->company(),
                    'starts_at' => $date->format('Y-m-d').' '.fake()->time('H:i:s'),
                    'ends_at' => $date->format('Y-m-d').' '.fake()->time('H:i:s', '+2 hours'),
                    'is_all_day' => false,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            $appointments->push($appointment);
        }

        return $appointments;
    }
}
