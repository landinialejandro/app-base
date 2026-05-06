{{-- FILE: resources/views/tasks/partials/table.blade.php | V8 --}}

@php
    use App\Support\Auth\TenantModuleAccess;
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Catalogs\TaskCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Orders\OrderLinked;
    use Illuminate\Support\Carbon;

    $tasks = $tasks ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay tareas para mostrar.';
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);

    $tenant = app('tenant');
    $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
@endphp

@if ($tasks->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Proyecto</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Asignado a</th>
                    <th>Vencimiento</th>
                    @if ($supportsOrdersModule)
                        <th>Orden</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'tasks.show',
                                $task->id,
                                $task->name ?: 'Tarea #' . $task->id,
                                route('tasks.show', ['task' => $task]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode('tasks.index', null, 'Tareas', route('tasks.index')),
                                NavigationTrail::makeNode(
                                    'tasks.show',
                                    $task->id,
                                    $task->name ?: 'Tarea #' . $task->id,
                                    route('tasks.show', ['task' => $task]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);

                        $dueText = '—';

                        if ($task->due_date) {
                            $today = now()->startOfDay();
                            $dueDate = Carbon::parse($task->due_date)->startOfDay();
                            $diffInDays = $today->diffInDays($dueDate, false);

                            $dueText = match (true) {
                                $diffInDays === 0 => $task->due_date->format('d/m/Y') . ' · Hoy',
                                $diffInDays === 1 => $task->due_date->format('d/m/Y') . ' · Mañana',
                                $diffInDays < 0 => $task->due_date->format('d/m/Y') . ' · Vencida',
                                default => $task->due_date->format('d/m/Y'),
                            };
                        }

                        $orderAction = $supportsOrdersModule
                            ? OrderLinked::forOrder($task->order, $rowTrailQuery, 'Orden')
                            : [];
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('tasks.show', ['task' => $task] + $rowTrailQuery) }}">
                                {{ $task->name }}
                            </a>
                        </td>

                        <td>
                            @if ($task->project)
                                <a href="{{ route('projects.show', ['project' => $task->project] + $rowTrailQuery) }}">
                                    {{ $task->project->name }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <span class="status-badge {{ TaskCatalog::priorityBadgeClass($task->priority) }}">
                                {{ TaskCatalog::priorityLabel($task->priority) }}
                            </span>
                        </td>

                        <td>
                            <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                                {{ TaskCatalog::label($task->status) }}
                            </span>
                        </td>

                        <td>{{ $task->assignedUser?->name ?? 'Sin asignar' }}</td>
                        <td>{{ $dueText }}</td>

                        @if ($supportsOrdersModule)
                            <td>
                                @include('orders.components.linked-order', [
                                    'action' => $orderAction,
                                    'variant' => 'inline',
                                ])
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif

<x-dev-component-version name="tasks.partials.table" version="V8" align="right" />