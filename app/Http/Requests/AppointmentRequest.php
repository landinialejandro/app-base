<?php

// FILE: app/Http/Requests/AppointmentRequest.php | V2

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controller via Policy
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)->whereNull('deleted_at')),
            ],
            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)->whereNull('deleted_at')),
            ],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)->whereNull('deleted_at')),
            ],
            'assigned_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'kind' => ['required', Rule::in(array_keys(AppointmentCatalog::kindLabels()))],
            'status' => ['required', Rule::in(AppointmentCatalog::statuses())],
            'work_mode' => ['nullable', Rule::in(array_keys(AppointmentCatalog::workModeLabels()))],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'workstation_name' => ['nullable', 'string', 'max:255'],
            'scheduled_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_all_day' => ['nullable', 'boolean'],
            'confirm_foreign_appointment_edit' => ['nullable', 'string'],
        ];
    }

    protected function passedValidation()
    {
        $tenant = app('tenant');
        $data = $this->validated();
        $appointment = $this->route('appointment'); // null en store

        $this->validateEnabledModules($data);
        $this->validateAssignedUserBelongsToTenant($data['assigned_user_id'], $tenant->id);
        $this->validateChronology($data);

        if (! $appointment) {
            $this->validateCreateDateRules($data);
        } else {
            $this->validateUpdateDateRules($data, $appointment);
        }

        $this->validateOverlap($data, $appointment);
    }

    protected function validateEnabledModules(array $data): void
    {
        $tenant = app('tenant');

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant) && ! empty($data['party_id'])) {
            throw ValidationException::withMessages([
                'party_id' => 'No puedes vincular un contacto porque el módulo no está habilitado para la empresa actual.',
            ]);
        }

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant) && ! empty($data['asset_id'])) {
            throw ValidationException::withMessages([
                'asset_id' => 'No puedes vincular un activo porque el módulo no está habilitado para la empresa actual.',
            ]);
        }

        if (! TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant) && ! empty($data['order_id'])) {
            throw ValidationException::withMessages([
                'order_id' => 'No puedes vincular una orden porque el módulo no está habilitado para la empresa actual.',
            ]);
        }
    }

    protected function validateAssignedUserBelongsToTenant(int $userId, string $tenantId): void
    {
        $exists = User::whereKey($userId)
            ->whereHas('memberships', fn ($q) => $q->where('tenant_id', $tenantId)->where('status', 'active'))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['assigned_user_id' => 'El colaborador asignado no pertenece a la empresa actual.']);
        }
    }

    protected function validateChronology(array &$data): void
    {
        $data['is_all_day'] = (bool) ($data['is_all_day'] ?? false);

        if ($data['is_all_day']) {
            return;
        }

        if (! empty($data['starts_at']) && ! empty($data['ends_at'])) {
            $start = Carbon::parse($data['starts_at']);
            $end = Carbon::parse($data['ends_at']);

            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'La hora de finalización debe ser mayor a la hora de inicio.',
                ]);
            }
        }
    }

    protected function validateOverlap(array $data, ?Appointment $currentAppointment = null): void
    {
        if (($data['status'] ?? null) === AppointmentCatalog::STATUS_CANCELLED) {
            return;
        }

        $query = Appointment::query()
            ->where('assigned_user_id', $data['assigned_user_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->whereIn('status', AppointmentCatalog::blockingStatuses());

        if ($currentAppointment) {
            $query->where('id', '!=', $currentAppointment->id);
        }

        if (! empty($data['is_all_day']) && $query->exists()) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'Ya existe un turno activo para ese colaborador en esa fecha.',
            ]);
        }

        if (! empty($data['starts_at']) && ! empty($data['ends_at'])) {
            $exists = $query
                ->where(fn ($q) => $q
                    ->where('is_all_day', true)
                    ->orWhere(fn ($rq) => $rq
                        ->whereNotNull('starts_at')
                        ->where('starts_at', '<', $data['ends_at'])
                        ->where('ends_at', '>', $data['starts_at'])))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'starts_at' => 'El colaborador ya tiene otro turno que se superpone en ese horario.',
                ]);
            }
        }
    }

    protected function validateCreateDateRules(array $data): void
    {
        if (Carbon::parse($data['scheduled_date'])->startOfDay()->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'No se pueden crear turnos en fechas anteriores a hoy.',
            ]);
        }
    }

    protected function validateUpdateDateRules(array $data, Appointment $appointment): void
    {
        $scheduledDate = Carbon::parse($data['scheduled_date'])->startOfDay();
        $today = now()->startOfDay();

        if ($appointment->status === AppointmentCatalog::STATUS_COMPLETED) {
            $originalDate = $appointment->scheduled_date?->copy()?->startOfDay();

            if ($originalDate && ! $scheduledDate->equalTo($originalDate)) {
                throw ValidationException::withMessages([
                    'scheduled_date' => 'Un turno completado no puede moverse de fecha.',
                ]);
            }

            return;
        }

        if ($scheduledDate->lt($today)) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'Solo puedes mover el turno a hoy o a una fecha posterior.',
            ]);
        }
    }
}
