<?php

// FILE: app/Http/Controllers/AppointmentController.php | V2

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Models\User;
use App\Support\Catalogs\AppointmentCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('viewAny', Appointment::class);

        $q = trim((string) $request->get('q', ''));
        $assignedUserId = $request->get('assigned_user_id');
        $partyId = $request->get('party_id');
        $kind = $request->get('kind');
        $status = $request->get('status');
        $scheduledDate = $request->get('scheduled_date');
        $scope = $request->get('scope', 'mine');

        $resolver = app(\App\Support\Auth\RolePermissionResolver::class);
        $updateScope = $resolver->actionScope(\App\Support\Catalogs\ModuleCatalog::APPOINTMENTS, 'update', app('tenant'), auth()->user());

        if ($updateScope !== 'all' && $scope === 'all') {
            $scope = 'mine';
        }

        if (! in_array($scope, ['mine', 'all'], true)) {
            $scope = 'mine';
        }

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $parties = Party::query()
            ->orderBy('name')
            ->get();

        $appointments = Appointment::query()
            ->with(['party', 'order', 'asset', 'assignedUser'])
            ->when($scope === 'mine', function ($query) {
                $query->where('assigned_user_id', auth()->id());
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subquery) use ($q) {
                    $subquery->where('title', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");

                    if (ctype_digit($q)) {
                        $subquery->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
            ->when($partyId, fn ($query) => $query->where('party_id', $partyId))
            ->when($kind, fn ($query) => $query->where('kind', $kind))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($scheduledDate, fn ($query) => $query->whereDate('scheduled_date', $scheduledDate))
            ->orderBy('scheduled_date')
            ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('appointments.index', compact(
            'appointments',
            'users',
            'parties',
            'scope'
        ));
    }

    public function calendar(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('viewAny', Appointment::class);

        $scope = $this->resolveCalendarScope($request);
        $assignedUserId = $request->get('assigned_user_id');
        $status = $request->get('status');

        $monthInput = (string) $request->get('month', now()->format('Y-m'));
        $baseMonth = $this->resolveCalendarMonth($monthInput);

        $monthStart = $baseMonth->copy()->startOfMonth();
        $monthEnd = $baseMonth->copy()->endOfMonth();

        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $appointments = Appointment::query()
            ->with(['party', 'asset', 'order', 'assignedUser'])
            ->whereBetween('scheduled_date', [
                $gridStart->toDateString(),
                $gridEnd->toDateString(),
            ])
            ->when($scope === 'mine', function ($query) {
                $query->where('assigned_user_id', auth()->id());
            })
            ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('scheduled_date')
            ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $appointmentsByDate = $appointments
            ->groupBy(fn (Appointment $appointment) => $appointment->scheduled_date?->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $weekStart = $cursor->copy();
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->copy();
                $dateKey = $date->toDateString();

                $days[] = [
                    'date' => $date,
                    'date_key' => $dateKey,
                    'is_current_month' => $date->month === $baseMonth->month,
                    'is_today' => $date->isToday(),
                    'appointments' => $appointmentsByDate->get($dateKey, collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = [
                'week_number' => $weekStart->isoWeek(),
                'days' => $days,
            ];
        }

        $previousMonth = $baseMonth->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $baseMonth->copy()->addMonthNoOverflow()->format('Y-m');

        return view('appointments.calendar', [
            'weeks' => $weeks,
            'users' => $users,
            'scope' => $scope,
            'selectedAssignedUserId' => $assignedUserId,
            'selectedStatus' => $status,
            'currentMonth' => $baseMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');

        $this->authorize('create', Appointment::class);

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $parties = Party::query()->orderBy('name')->get();
        $orders = Order::query()->with('party')->latest()->limit(100)->get();
        $assets = Asset::query()->with('party')->orderBy('name')->get();

        $defaultAssignedUserId = old('assigned_user_id', (string) auth()->id());

        return view('appointments.create', compact(
            'users',
            'parties',
            'orders',
            'assets',
            'defaultAssignedUserId'
        ));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('create', Appointment::class);

        $data = $this->validateAppointment($request, $tenant);

        $this->validateAssignedUserBelongsToTenant($data['assigned_user_id'], $tenant->id);
        $this->validateChronology($data);
        $this->validateCreateDateRules($data);
        $this->validateOverlap($data);

        $data['created_by'] = auth()->id();

        $appointment = Appointment::create($data);

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('success', 'Turno creado correctamente.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'party',
            'order',
            'asset',
            'assignedUser',
            'creator',
            'updater',
        ]);

        $canEditAppointment = auth()->user()->can('update', $appointment);
        $canDeleteAppointment = auth()->user()->can('delete', $appointment);
        $isForeignAppointmentForAdmin = $canDeleteAppointment
            && (int) $appointment->assigned_user_id !== (int) auth()->id();

        return view('appointments.show', compact(
            'appointment',
            'canEditAppointment',
            'canDeleteAppointment',
            'isForeignAppointmentForAdmin'
        ));
    }

    public function edit(Appointment $appointment)
    {
        $tenant = app('tenant');

        $this->authorize('update', $appointment);

        $users = User::query()
            ->whereHas('memberships', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();

        $parties = Party::query()->orderBy('name')->get();
        $orders = Order::query()->with('party')->latest()->limit(100)->get();
        $assets = Asset::query()->with('party')->orderBy('name')->get();

        $defaultAssignedUserId = old('assigned_user_id', (string) $appointment->assigned_user_id);
        $isForeignAppointmentForAdmin = auth()->user()->can('delete', $appointment)
            && (int) $appointment->assigned_user_id !== (int) auth()->id();

        return view('appointments.edit', compact(
            'appointment',
            'users',
            'parties',
            'orders',
            'assets',
            'defaultAssignedUserId',
            'isForeignAppointmentForAdmin'
        ));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $tenant = app('tenant');

        $this->authorize('update', $appointment);

        $data = $this->validateAppointment($request, $tenant, $appointment);

        $this->validateAssignedUserBelongsToTenant($data['assigned_user_id'], $tenant->id);
        $this->validateChronology($data);
        $this->validateUpdateDateRules($data, $appointment);

        $isAdminEditingForeignAppointment = auth()->user()->can('delete', $appointment)
            && (int) $appointment->assigned_user_id !== (int) auth()->id();

        if ($isAdminEditingForeignAppointment && $request->input('confirm_foreign_appointment_edit') !== '1') {
            throw ValidationException::withMessages([
                'confirm_foreign_appointment_edit' => 'Estás editando un turno asignado a otro colaborador. Confirmá la modificación antes de guardar.',
            ]);
        }

        $this->validateOverlap($data, $appointment);

        unset($data['confirm_foreign_appointment_edit']);

        $data['updated_by'] = auth()->id();

        $appointment->update($data);

        return redirect()
            ->route('appointments.show', $appointment)
            ->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Turno eliminado correctamente.');
    }

    protected function validateAppointment(Request $request, $tenant, ?Appointment $appointment = null): array
    {
        return $request->validate([
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
            ],
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)->whereNull('deleted_at');
                }),
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
        ]);
    }

    protected function validateAssignedUserBelongsToTenant(int $userId, string $tenantId): void
    {
        $userBelongsToTenant = User::query()
            ->whereKey($userId)
            ->whereHas('memberships', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->where('status', 'active');
            })
            ->exists();

        if (! $userBelongsToTenant) {
            throw ValidationException::withMessages([
                'assigned_user_id' => 'El colaborador asignado no pertenece a la empresa actual.',
            ]);
        }
    }

    protected function validateChronology(array &$data): void
    {
        $data['is_all_day'] = (bool) ($data['is_all_day'] ?? false);

        $hasStartsAt = ! empty($data['starts_at']);
        $hasEndsAt = ! empty($data['ends_at']);

        if ($hasStartsAt xor $hasEndsAt) {
            throw ValidationException::withMessages([
                'starts_at' => 'Si cargas horario, debes completar inicio y fin.',
                'ends_at' => 'Si cargas horario, debes completar inicio y fin.',
            ]);
        }

        if ($data['is_all_day']) {
            $data['starts_at'] = null;
            $data['ends_at'] = null;

            return;
        }

        if ($hasStartsAt && $hasEndsAt) {
            $start = Carbon::parse($data['starts_at']);
            $end = Carbon::parse($data['ends_at']);
            $scheduledDate = Carbon::parse($data['scheduled_date'])->toDateString();

            if ($start->toDateString() < $scheduledDate) {
                throw ValidationException::withMessages([
                    'starts_at' => 'La fecha y hora de inicio no pueden ser anteriores a la fecha programada.',
                ]);
            }

            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'La hora de finalización debe ser mayor a la hora de inicio.',
                ]);
            }

            $data['starts_at'] = $start;
            $data['ends_at'] = $end;
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

        if (! empty($data['is_all_day'])) {
            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'scheduled_date' => 'Ya existe un turno activo para ese colaborador en esa fecha.',
                ]);
            }

            return;
        }

        $hasTimeRange = ! empty($data['starts_at']) && ! empty($data['ends_at']);

        if (! $hasTimeRange) {
            return;
        }

        $exists = $query
            ->where(function ($subquery) use ($data) {
                $subquery
                    ->where('is_all_day', true)
                    ->orWhere(function ($rangeQuery) use ($data) {
                        $rangeQuery
                            ->whereNotNull('starts_at')
                            ->whereNotNull('ends_at')
                            ->where('starts_at', '<', $data['ends_at'])
                            ->where('ends_at', '>', $data['starts_at']);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'starts_at' => 'El colaborador ya tiene otro turno que se superpone en ese horario.',
                'ends_at' => 'El colaborador ya tiene otro turno que se superpone en ese horario.',
            ]);
        }
    }

    protected function resolveCalendarScope(Request $request): string
    {
        $scope = $request->get('scope', 'mine');

        $resolver = app(\App\Support\Auth\RolePermissionResolver::class);
        $updateScope = $resolver->actionScope(
            \App\Support\Catalogs\ModuleCatalog::APPOINTMENTS,
            'update',
            app('tenant'),
            auth()->user()
        );

        if ($updateScope !== 'all' && $scope === 'all') {
            return 'mine';
        }

        return in_array($scope, ['mine', 'all'], true) ? $scope : 'mine';
    }

    protected function resolveCalendarMonth(string $monthInput): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $monthInput) !== 1) {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }

    protected function validateCreateDateRules(array $data): void
    {
        $scheduledDate = Carbon::parse($data['scheduled_date'])->startOfDay();
        $today = now()->startOfDay();

        if ($scheduledDate->lt($today)) {
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

            if (! $originalDate || ! $scheduledDate->equalTo($originalDate)) {
                throw ValidationException::withMessages([
                    'scheduled_date' => 'Un turno completado no puede moverse de fecha.',
                ]);
            }

            if (
                ! empty($data['starts_at']) && $appointment->starts_at &&
                ! Carbon::parse($data['starts_at'])->equalTo($appointment->starts_at)
            ) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Un turno completado no puede modificar su horario.',
                ]);
            }

            if (
                ! empty($data['ends_at']) && $appointment->ends_at &&
                ! Carbon::parse($data['ends_at'])->equalTo($appointment->ends_at)
            ) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Un turno completado no puede modificar su horario.',
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
