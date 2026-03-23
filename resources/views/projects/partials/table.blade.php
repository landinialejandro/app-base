{{-- FILE: resources/views/projects/partials/table.blade.php | V2 --}}

@php
    use App\Support\Catalogs\ProjectCatalog;
    use Illuminate\Support\Carbon;

    $projects = $projects ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay proyectos para mostrar.';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($projects->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Estado</th>
                    <th>Abiertas</th>
                    <th>En progreso</th>
                    <th>Vencidas</th>
                    <th>Avance</th>
                    <th>Próximo vencimiento</th>
                    <th>Actualizado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    @php
                        $tasksCount = (int) ($project->tasks_count ?? 0);
                        $doneCount = (int) ($project->done_tasks_count ?? 0);
                        $progress = $tasksCount > 0 ? round(($doneCount / $tasksCount) * 100) : 0;

                        $nextDueText = '—';

                        if (!empty($project->next_due_date)) {
                            $nextDueDate = Carbon::parse($project->next_due_date)->startOfDay();
                            $today = now()->startOfDay();
                            $diffInDays = $today->diffInDays($nextDueDate, false);

                            $nextDueText = match (true) {
                                $diffInDays === 0 => $nextDueDate->format('d/m/Y') . ' · Hoy',
                                $diffInDays === 1 => $nextDueDate->format('d/m/Y') . ' · Mañana',
                                $diffInDays < 0 => $nextDueDate->format('d/m/Y') . ' · Vencido',
                                default => $nextDueDate->format('d/m/Y'),
                            };
                        }
                    @endphp

                    <tr>
                        <td>
                            <div>
                                <a href="{{ route('projects.show', ['project' => $project] + $trailQuery) }}">
                                    {{ $project->name }}
                                </a>
                            </div>
                            <div class="table-cell-help">
                                #{{ $project->id }}
                            </div>
                        </td>

                        <td>
                            <span class="status-badge {{ ProjectCatalog::badgeClass($project->status) }}">
                                {{ ProjectCatalog::label($project->status) }}
                            </span>
                        </td>

                        <td>{{ (int) ($project->open_tasks_count ?? 0) }}</td>
                        <td>{{ (int) ($project->in_progress_tasks_count ?? 0) }}</td>
                        <td>{{ (int) ($project->overdue_tasks_count ?? 0) }}</td>
                        <td>{{ $progress }}%</td>
                        <td>{{ $nextDueText }}</td>
                        <td>{{ $project->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
