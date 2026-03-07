@extends('layouts.app')

@section('title', 'Proyectos')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos'],
        ]" />

        <x-page-header title="Proyectos">
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                Nuevo proyecto
            </a>
        </x-page-header>

        <x-card>
            @if ($projects->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
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
                                    <td>{{ $project->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>No hay proyectos cargados.</p>
            @endif
        </x-card>
    </x-page>
@endsection