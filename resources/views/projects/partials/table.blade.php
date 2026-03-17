{{-- FILE: resources/views/projects/partials/table.blade.php --}}

@php
    $projects = $projects ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay proyectos para mostrar.';
@endphp

@if ($projects->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Creado</th>
                    <th>Actualizado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>
                            <a href="{{ route('projects.show', $project) }}">
                                {{ $project->name }}
                            </a>
                        </td>
                        <td>{{ $project->description ?: '—' }}</td>
                        <td>{{ $project->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $project->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
