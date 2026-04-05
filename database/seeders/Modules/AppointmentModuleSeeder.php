<?php

// FILE: database/seeders/Modules/AppointmentModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class AppointmentModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (
            ! $this->hasDependency('tenants')
            || ! $this->hasDependency('users')
            || ! $this->hasDependency('parties')
        ) {
            throw new \RuntimeException('AppointmentModuleSeeder requires tenants, users, and parties');
        }

        $tenants = $this->getDependency('tenants');
        $users = $this->getDependency('users');
        $parties = $this->getDependency('parties');
        $assets = $this->getDependency('assets');

        $appointments = [];

        $appointments['tech'] = $this->createTechAppointments(
            tenantId: $tenants['tech']->id,
            users: collect([
                $users['ownerTech'],
                $users['shared'],
                $users['techUser'],
            ]),
            parties: $parties['techFixed']->merge($parties['techExtra']),
            assets: $assets['tech'] ?? collect(),
            targetCount: (int) config('seeders.demo.tech.target_appointments', 8),
        );

        $appointments['andina'] = $this->createAndinaAppointments(
            tenantId: $tenants['andina']->id,
            users: collect([
                $users['ownerAndina'],
                $users['shared'],
                $users['andinaUser'],
            ]),
            parties: $parties['andinaFixed']->merge($parties['andinaExtra']),
            assets: $assets['andina'] ?? collect(),
            targetCount: (int) config('seeders.demo.andina.target_appointments', 6),
        );

        $this->context['appointments'] = $appointments;
    }

    private function createTechAppointments(
        string $tenantId,
        Collection $users,
        Collection $parties,
        Collection $assets,
        int $targetCount
    ): Collection {
        $appointments = collect();

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Visita técnica inicial',
            'scheduled_date' => now()->subDays(2)->toDateString(),
            'party_id' => $parties->first()?->id,
            'asset_id' => $assets->first()?->id,
            'assigned_user_id' => $users->get(2)?->id,
            'kind' => 'visit',
            'status' => 'completed',
            'work_mode' => 'on_site',
            'notes' => 'Turno histórico reciente asignado al operador/comercial del tenant.',
            'workstation_name' => 'Cliente principal',
            'starts_at' => now()->subDays(2)->setTime(9, 0, 0),
            'ends_at' => now()->subDays(2)->setTime(10, 0, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->first()?->id,
        ]));

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Bloqueo operativo taller',
            'scheduled_date' => now()->toDateString(),
            'party_id' => null,
            'asset_id' => null,
            'assigned_user_id' => $users->first()?->id,
            'kind' => 'block',
            'status' => 'scheduled',
            'work_mode' => 'in_shop',
            'notes' => 'Bloqueo operativo asignado para respetar la restricción real del esquema.',
            'workstation_name' => 'Taller',
            'starts_at' => now()->setTime(8, 0, 0),
            'ends_at' => now()->setTime(12, 0, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->first()?->id,
        ]));

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Servicio programado',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'party_id' => $parties->skip(1)->first()?->id,
            'asset_id' => $assets->skip(1)->first()?->id,
            'assigned_user_id' => $users->get(1)?->id,
            'kind' => 'service',
            'status' => 'confirmed',
            'work_mode' => 'field_assistance',
            'notes' => 'Caso asignado a usuario compartido.',
            'workstation_name' => 'Campo',
            'starts_at' => now()->addDays(4)->setTime(14, 0, 0),
            'ends_at' => now()->addDays(4)->setTime(16, 0, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->get(1)?->id,
        ]));

        return $appointments->merge(
            $this->generateDeterministicAppointments(
                tenantId: $tenantId,
                users: $users,
                parties: $parties,
                assets: $assets,
                targetCount: $targetCount,
                fixedCount: $appointments->count(),
                seedPrefix: 'TECH'
            )
        );
    }

    private function createAndinaAppointments(
        string $tenantId,
        Collection $users,
        Collection $parties,
        Collection $assets,
        int $targetCount
    ): Collection {
        $appointments = collect();

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Visita a obra',
            'scheduled_date' => now()->subDays(3)->toDateString(),
            'party_id' => $parties->first()?->id,
            'asset_id' => $assets->first()?->id,
            'assigned_user_id' => $users->get(2)?->id,
            'kind' => 'visit',
            'status' => 'completed',
            'work_mode' => 'on_site',
            'notes' => 'Turno histórico reciente asignado al operador de obra.',
            'workstation_name' => 'Base de obra',
            'starts_at' => now()->subDays(3)->setTime(8, 30, 0),
            'ends_at' => now()->subDays(3)->setTime(10, 30, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->first()?->id,
        ]));

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Bloqueo de maquinaria',
            'scheduled_date' => now()->addDays(1)->toDateString(),
            'party_id' => null,
            'asset_id' => $assets->skip(1)->first()?->id,
            'assigned_user_id' => $users->first()?->id,
            'kind' => 'block',
            'status' => 'scheduled',
            'work_mode' => 'field_assistance',
            'notes' => 'Bloqueo operativo asignado para respetar la restricción real del esquema.',
            'workstation_name' => 'Zona operativa',
            'starts_at' => now()->addDays(1)->setTime(13, 0, 0),
            'ends_at' => now()->addDays(1)->setTime(17, 0, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->first()?->id,
        ]));

        $appointments->push($this->createAppointment([
            'tenant_id' => $tenantId,
            'title' => 'Servicio de inspección',
            'scheduled_date' => now()->addDays(6)->toDateString(),
            'party_id' => $parties->skip(1)->first()?->id,
            'asset_id' => $assets->first()?->id,
            'assigned_user_id' => $users->get(1)?->id,
            'kind' => 'service',
            'status' => 'confirmed',
            'work_mode' => 'on_site',
            'notes' => 'Caso asignado a usuario administrativo compartido.',
            'workstation_name' => 'Inspección',
            'starts_at' => now()->addDays(6)->setTime(11, 0, 0),
            'ends_at' => now()->addDays(6)->setTime(12, 30, 0),
            'is_all_day' => false,
            'created_by' => $users->first()?->id,
            'updated_by' => $users->get(1)?->id,
        ]));

        return $appointments->merge(
            $this->generateDeterministicAppointments(
                tenantId: $tenantId,
                users: $users,
                parties: $parties,
                assets: $assets,
                targetCount: $targetCount,
                fixedCount: $appointments->count(),
                seedPrefix: 'AND'
            )
        );
    }

    private function generateDeterministicAppointments(
        string $tenantId,
        Collection $users,
        Collection $parties,
        Collection $assets,
        int $targetCount,
        int $fixedCount,
        string $seedPrefix
    ): Collection {
        $appointments = collect();
        $remaining = max(0, $targetCount - $fixedCount);

        $kinds = ['service', 'visit', 'block'];
        $workModes = ['in_shop', 'on_site', 'field_assistance'];

        $dayOffsets = [-3, -2, -1, 0, 1, 3, 5, 7, 10, 14];

        for ($i = 1; $i <= $remaining; $i++) {
            $offset = $dayOffsets[($i - 1) % count($dayOffsets)];
            $date = now()->copy()->addDays($offset);

            $kind = $kinds[($i - 1) % count($kinds)];
            $workMode = $workModes[($i - 1) % count($workModes)];

            $status = $this->statusForAppointmentOffset($offset, $i);

            $assignedUserId = $users[($i - 1) % max(1, $users->count())]?->id;

            $party = $parties->isNotEmpty() && $i % 3 !== 0
                ? $parties[($i - 1) % $parties->count()]
                : null;

            $asset = $assets->isNotEmpty() && $i % 2 === 0
                ? $assets[($i - 1) % $assets->count()]
                : null;

            $startHour = 9 + (($i - 1) % 6);

            $appointments->push($this->createAppointment([
                'tenant_id' => $tenantId,
                'title' => sprintf('%s turno %02d', $seedPrefix, $i),
                'scheduled_date' => $date->toDateString(),
                'party_id' => $party?->id,
                'asset_id' => $asset?->id,
                'assigned_user_id' => $assignedUserId,
                'kind' => $kind,
                'status' => $status,
                'work_mode' => $workMode,
                'notes' => sprintf('Turno demo %02d del tenant %s.', $i, $seedPrefix),
                'workstation_name' => sprintf('%s base %02d', $seedPrefix, $i),
                'starts_at' => $date->copy()->setTime($startHour, 0, 0),
                'ends_at' => $date->copy()->setTime($startHour + 1, 30, 0),
                'is_all_day' => false,
                'created_by' => $users->first()?->id,
                'updated_by' => $users->first()?->id,
            ]));
        }

        return $appointments;
    }

    private function statusForAppointmentOffset(int $offset, int $index): string
    {
        if ($offset < 0) {
            return $index % 5 === 0 ? 'cancelled' : 'completed';
        }

        if ($offset === 0) {
            return $index % 3 === 0 ? 'confirmed' : 'scheduled';
        }

        return $index % 4 === 0 ? 'cancelled' : 'scheduled';
    }

    private function createAppointment(array $data): Appointment
    {
        $appointment = Appointment::query()
            ->where('tenant_id', $data['tenant_id'])
            ->where('title', $data['title'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->first();

        $payload = [
            'party_id' => $data['party_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'asset_id' => $data['asset_id'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'],
            'kind' => $data['kind'],
            'status' => $data['status'],
            'work_mode' => $data['work_mode'],
            'notes' => $data['notes'] ?? null,
            'workstation_name' => $data['workstation_name'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_all_day' => (bool) ($data['is_all_day'] ?? false),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];

        if ($appointment) {
            $appointment->update($payload);

            return $appointment;
        }

        return Appointment::create(array_merge([
            'tenant_id' => $data['tenant_id'],
            'title' => $data['title'],
            'scheduled_date' => $data['scheduled_date'],
        ], $payload));
    }
}
