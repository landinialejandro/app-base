{{-- FILE: resources/views/tasks/partials/table.blade.php | V3 --}}

@php
    use App\Support\Catalogs\TaskCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Navigation\TaskNavigationTrail;
    use Illuminate\Support\Carbon;

    $tasks = $tasks ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay tareas para mostrar.';
    $trailQuery = $trailQuery ?? [];
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
                    <th>Orden</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    @php
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

                        $taskTrailQuery = NavigationTrail::toQuery(TaskNavigationTrail::base($task));
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('tasks.show', ['task' => $task] + $trailQuery) }}">
                                {{ $task->name }}
                            </a>
                        </td>

                        <td>
                            @if ($task->project)
                                <a href="{{ route('projects.show', ['project' => $task->project] + $trailQuery) }}">
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
                        <td>
                            @if ($task->order)
                                <a href="{{ route('orders.show', ['order' => $task->order] + $taskTrailQuery) }}">
                                    {{ $task->order->number ?: 'Ver orden' }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
