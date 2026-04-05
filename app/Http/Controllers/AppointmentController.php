<?php

// FILE: app/Http/Controllers/AppointmentController.php | V11

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Party;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\AppointmentCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Navigation\AppointmentNavigationTrail;
use App\Support\Navigation\NavigationTrail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $canViewAllAppointments = $this->canViewAllAppointments();

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

        $appointments = app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->with(['party', 'order', 'asset', 'assignedUser'])
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
            ->orderByDesc('is_all_day')
            ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_at')
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'users' => $users,
            'parties' => $parties,
            'scope' => $canViewAllAppointments
                ? PermissionScopeCatalog::TENANT_ALL
                : PermissionScopeCatalog::OWN_ASSIGNED,
            'canViewAllAppointments' => $canViewAllAppointments,
        ]);
    }

    public function calendar(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('viewAny', Appointment::class);

        $canViewAllAppointments = $this->canViewAllAppointments();
        $assignedUserId = $request->get('assigned_user_id');
        $status = $request->get('status');
        $view = $this->resolveCalendarView((string) $request->get('view', 'month'));

        $baseDate = $this->resolveCalendarDate((string) $request->get('date', now()->toDateString()));
        $baseMonth = $this->resolveCalendarMonth((string) $request->get('month', now()->format('Y-m')));

        if ($view === 'week') {
            $weekStart = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);

            $users = User::query()
                ->whereHas('memberships', function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->where('status', 'active');
                })
                ->orderBy('name')
                ->get();

            $appointments = app(Security::class)
                ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
                ->with(['party', 'asset', 'order', 'assignedUser'])
                ->whereBetween('scheduled_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ])
                ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('scheduled_date')
                ->orderByDesc('is_all_day')
                ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('starts_at')
                ->orderBy('created_at')
                ->get();

            $appointmentsByDate = $appointments
                ->groupBy(fn (Appointment $appointment) => $appointment->scheduled_date?->toDateString());

            $days = [];
            $cursor = $weekStart->copy();

            while ($cursor->lte($weekEnd)) {
                $date = $cursor->copy();
                $dateKey = $date->toDateString();

                $days[] = [
                    'date' => $date,
                    'date_key' => $dateKey,
                    'is_current_month' => true,
                    'is_today' => $date->isToday(),
                    'appointments' => $appointmentsByDate->get($dateKey, collect()),
                ];

                $cursor->addDay();
            }

            return view('appointments.calendar', [
                'viewMode' => 'week',
                'days' => $days,
                'users' => $users,
                'scope' => $canViewAllAppointments
                    ? PermissionScopeCatalog::TENANT_ALL
                    : PermissionScopeCatalog::OWN_ASSIGNED,
                'canViewAllAppointments' => $canViewAllAppointments,
                'selectedAssignedUserId' => $assignedUserId,
                'selectedStatus' => $status,
                'currentDate' => $baseDate,
                'currentWeekStart' => $weekStart,
                'currentWeekEnd' => $weekEnd,
                'previousDate' => $weekStart->copy()->subWeek()->toDateString(),
                'nextDate' => $weekStart->copy()->addWeek()->toDateString(),
            ]);
        }

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

        $appointments = app(Security::class)
            ->scope(auth()->user(), 'appointments.viewAny', Appointment::query())
            ->with(['party', 'asset', 'order', 'assignedUser'])
            ->whereBetween('scheduled_date', [
                $gridStart->toDateString(),
                $gridEnd->toDateString(),
            ])
            ->when($assignedUserId, fn ($query) => $query->where('assigned_user_id', $assignedUserId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('scheduled_date')
            ->orderByDesc('is_all_day')
            ->orderByRaw('CASE WHEN starts_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_at')
            ->orderBy('created_at')
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
                'week_start_date' => $weekStart->toDateString(),
                'days' => $days,
            ];
        }

        $previousMonth = $baseMonth->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $baseMonth->copy()->addMonthNoOverflow()->format('Y-m');

        return view('appointments.calendar', [
            'viewMode' => 'month',
            'weeks' => $weeks,
            'users' => $users,
            'scope' => $canViewAllAppointments
                ? PermissionScopeCatalog::TENANT_ALL
                : PermissionScopeCatalog::OWN_ASSIGNED,
            'canViewAllAppointments' => $canViewAllAppointments,
            'selectedAssignedUserId' => $assignedUserId,
            'selectedStatus' => $status,
            'currentMonth' => $baseMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function create(Request $request)
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

        $prefilledPartyId = null;
        $prefilledAssetId = null;
        $prefilledOrderId = null;
        $prefilledScheduledDate = null;

        if ($request->filled('party_id')) {
            $party = Party::query()
                ->where('id', $request->integer('party_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->first();

            if ($party) {
                $prefilledPartyId = $party->id;
            }
        }

        if ($request->filled('asset_id')) {
            $asset = Asset::query()
                ->with('party')
                ->where('id', $request->integer('asset_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->first();

            if ($asset) {
                $prefilledAssetId = $asset->id;
                $prefilledPartyId = $asset->party_id;
            }
        }

        if ($request->filled('order_id')) {
            $order = Order::query()
                ->with(['party', 'asset'])
                ->where('id', $request->integer('order_id'))
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->first();

            if ($order) {
                $prefilledOrderId = $order->id;
                $prefilledPartyId = $order->party_id;
                $prefilledAssetId = $order->asset_id;
            }
        }

        if ($request->filled('scheduled_date')) {
            $scheduledDateInput = (string) $request->get('scheduled_date');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDateInput) === 1) {
                $prefilledScheduledDate = $scheduledDateInput;
            }
        }

        $defaultAssignedUserId = old('assigned_user_id', (string) auth()->id());
        $navigationTrail = AppointmentNavigationTrail::create($request);

        return view('appointments.create', compact(
            'users',
            'parties',
            'orders',
            'assets',
            'defaultAssignedUserId',
            'navigationTrail',
            'prefilledPartyId',
            'prefilledAssetId',
            'prefilledOrderId',
            'prefilledScheduledDate',
        ));
    }

    public function store(AppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $appointment = Appointment::create($data);
        $navigationTrail = AppointmentNavigationTrail::show($request, $appointment);

        return redirect()
            ->route('appointments.show', ['appointment' => $appointment] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Turno creado correctamente.');
    }

    public function show(Request $request, Appointment $appointment)
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
        $isForeignAppointmentForAdmin = $this->canManageForeignAppointment($appointment);

        $navigationTrail = AppointmentNavigationTrail::show($request, $appointment);

        return view('appointments.show', compact(
            'appointment',
            'canEditAppointment',
            'canDeleteAppointment',
            'isForeignAppointmentForAdmin',
            'navigationTrail'
        ));
    }

    public function edit(Request $request, Appointment $appointment)
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
        $isForeignAppointmentForAdmin = $this->canManageForeignAppointment($appointment);
        $navigationTrail = AppointmentNavigationTrail::edit($request, $appointment);

        return view('appointments.edit', compact(
            'appointment',
            'users',
            'parties',
            'orders',
            'assets',
            'defaultAssignedUserId',
            'isForeignAppointmentForAdmin',
            'navigationTrail'
        ));
    }

    public function update(AppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validated();

        $isAdminEditingForeignAppointment = $this->canManageForeignAppointment($appointment);

        if ($isAdminEditingForeignAppointment && $request->input('confirm_foreign_appointment_edit') !== '1') {
            throw ValidationException::withMessages([
                'confirm_foreign_appointment_edit' => 'Estás editando un turno asignado a otro colaborador. Confirmá la modificación antes de guardar.',
            ]);
        }

        unset($data['confirm_foreign_appointment_edit']);

        $data['updated_by'] = auth()->id();

        $appointment->update($data);

        $navigationTrail = AppointmentNavigationTrail::show($request, $appointment);

        return redirect()
            ->route('appointments.show', ['appointment' => $appointment] + NavigationTrail::toQuery($navigationTrail))
            ->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $navigationTrail = AppointmentNavigationTrail::show($request, $appointment);
        $redirectUrl = NavigationTrail::previousUrl($navigationTrail, route('appointments.calendar', [
            'view' => 'month',
            'month' => now()->format('Y-m'),
        ]));

        $appointment->delete();

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Turno eliminado correctamente.');
    }

    protected function canViewAllAppointments(): bool
    {
        $inspection = app(Security::class)->inspect(auth()->user(), 'appointments.viewAny');

        return ($inspection['scope'] ?? null) === PermissionScopeCatalog::TENANT_ALL;
    }

    protected function canManageForeignAppointment(Appointment $appointment): bool
    {
        $inspection = app(Security::class)->inspect(auth()->user(), 'appointments.update', $appointment);

        return ($inspection['scope'] ?? null) === PermissionScopeCatalog::TENANT_ALL
            && (int) $appointment->assigned_user_id !== (int) auth()->id();
    }

    protected function resolveCalendarView(string $view): string
    {
        return in_array($view, ['month', 'week'], true) ? $view : 'month';
    }

    protected function resolveCalendarDate(string $dateInput): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput) !== 1) {
            return now()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateInput)->startOfDay();
        } catch (\Throwable $e) {
            return now()->startOfDay();
        }
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

    public function print(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'party',
            'order',
            'asset',
            'assignedUser',
        ]);

        return view('appointments.print', [
            'appointment' => $appointment,
            'renderMode' => 'print',
            'kindLabels' => AppointmentCatalog::kindLabels(),
            'statusLabels' => AppointmentCatalog::statusLabels(),
            'workModeLabels' => AppointmentCatalog::workModeLabels(),
        ]);
    }

    public function pdf(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'party',
            'order',
            'asset',
            'assignedUser',
        ]);

        $pdf = Pdf::loadView('appointments.print', [
            'appointment' => $appointment,
            'renderMode' => 'pdf',
            'kindLabels' => AppointmentCatalog::kindLabels(),
            'statusLabels' => AppointmentCatalog::statusLabels(),
            'workModeLabels' => AppointmentCatalog::workModeLabels(),
        ]);

        return $pdf->download('turno-'.$appointment->id.'.pdf');
    }
}
