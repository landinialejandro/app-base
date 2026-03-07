@extends('layouts.app')

@section('title', 'Detalle del proyecto')

@section('content')

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('projects.index')],
            ['label' => $project->name],
        ]" />
        <x-page-header title="Detalle del proyecto">
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                onsubmit="return confirm('¿Eliminar proyecto?');" class="inline-form">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>

            <div class="detail-list">
                <div class="detail-label">ID</div>
                <div class="detail-value">{{ $project->id }}</div>

                <div class="detail-label">Nombre</div>
                <div class="detail-value">{{ $project->name }}</div>

                <div class="detail-label">Descripción</div>
                <div class="detail-value">{{ $project->description }}</div>

                <div class="detail-label">Tenant</div>
                <div class="detail-value">{{ $tenant->name }}</div>
            </div>

        </x-card>

    </x-page>

@endsection