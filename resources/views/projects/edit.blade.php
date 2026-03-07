@extends('layouts.app')

@section('title', 'Editar proyecto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('projects.index')],
            ['label' => $project->name, 'url' => route('projects.show', $project)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar proyecto" />

        <x-card>
            <form method="POST" action="{{ route('projects.update', $project) }}" class="form">
                @csrf
                @method('PUT')

                @include('projects._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection