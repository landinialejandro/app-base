{{-- FILE: resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page>

        <x-page-header title="Dashboard" />

        <x-card>
            <div class="dashboard-tenant-summary">
                <div class="dashboard-tenant-main">
                    <span class="dashboard-tenant-label">Empresa actual</span>
                    <h2 class="dashboard-tenant-name">{{ $tenant->name }}</h2>
                    <p class="dashboard-tenant-slug">{{ $tenant->slug }}</p>
                </div>

                <div class="dashboard-tenant-stats">
                    <span class="dashboard-tenant-stat">
                        {{ $projectsCount }} proyectos
                    </span>
                    <span class="dashboard-tenant-stat">
                        {{ $tasksCount }} tareas
                    </span>
                    <span class="dashboard-tenant-stat">
                        {{ $partiesCount }} contactos
                    </span>
                </div>
            </div>
        </x-card>

        <x-card>
            <h2 class="dashboard-section-title">Accesos rápidos</h2>

            <div class="dashboard-grid">
                <a href="{{ route('projects.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Proyectos</span>
                    <span class="dashboard-link-text">Ver y administrar proyectos</span>
                    <span class="dashboard-link-meta">{{ $projectsCount }} proyectos</span>
                </a>

                <a href="{{ route('tasks.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Tareas</span>
                    <span class="dashboard-link-text">Ver y administrar tareas</span>
                    <span class="dashboard-link-meta">
                        {{ $tasksCount }} totales · {{ $tasksDoneCount }} completadas
                    </span>
                </a>

                <a href="{{ route('parties.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Contactos</span>
                    <span class="dashboard-link-text">Ver y administrar contactos</span>
                    <span class="dashboard-link-meta">{{ $partiesCount }} contactos</span>
                </a>
            </div>
        </x-card>

    </x-page>
@endsection