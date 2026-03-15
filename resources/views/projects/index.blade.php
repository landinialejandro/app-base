{{-- FILE: resources/views/projects/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Proyectos')

@section('content')
    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Proyectos']]" />

        <x-page-header title="Proyectos">
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                Nuevo proyecto
            </a>
        </x-page-header>

        <x-card class="list-card">

            <form method="GET" action="{{ route('projects.index') }}" class="form list-filters">
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, descripción o ID">
                    </div>
                </div>

                <div class="list-filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>

                    <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

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

                    {{ $projects->links() }}
                </div>
            @else
                <p class="mb-0">No hay proyectos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection
