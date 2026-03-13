{{-- FILE: resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio'],
        ]" />

        <x-page-header title="Dashboard" />

        <x-card>
            <div class="dashboard-tenant-summary">
                <div class="dashboard-tenant-main">
                    <span class="dashboard-tenant-label">Empresa actual</span>
                    <h2 class="dashboard-tenant-name">{{ $tenant->name }}</h2>
                    <p class="dashboard-tenant-slug">{{ $tenant->slug }}</p>
                </div>

                <div class="dashboard-tenant-stats">
                    <span class="dashboard-tenant-stat">{{ $projectsCount }} proyectos</span>
                    <span class="dashboard-tenant-stat">{{ $tasksCount }} tareas</span>
                    <span class="dashboard-tenant-stat">{{ $partiesCount }} contactos</span>
                    <span class="dashboard-tenant-stat">{{ $productsCount }} productos</span>
                    <span class="dashboard-tenant-stat">{{ $ordersCount }} órdenes</span>
                    <span class="dashboard-tenant-stat">{{ $documentsCount }} documentos</span>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Gestión operativa</h2>
                <p class="dashboard-section-text">Accesos rápidos para el trabajo diario y seguimiento.</p>
            </div>

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
                        {{ $tasksCount }} totales · {{ $tasksDoneCount }} finalizadas
                    </span>
                </a>

                <a href="{{ route('parties.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Contactos</span>
                    <span class="dashboard-link-text">Ver y administrar contactos</span>
                    <span class="dashboard-link-meta">{{ $partiesCount }} contactos</span>
                </a>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Gestión comercial</h2>
                <p class="dashboard-section-text">Accesos rápidos para productos, órdenes y documentos.</p>
            </div>

            <div class="dashboard-grid">
                <a href="{{ route('products.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Productos</span>
                    <span class="dashboard-link-text">Ver y administrar productos y servicios</span>
                    <span class="dashboard-link-meta">{{ $productsCount }} productos</span>
                </a>

                <a href="{{ route('orders.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Órdenes</span>
                    <span class="dashboard-link-text">Ver y administrar órdenes</span>
                    <span class="dashboard-link-meta">{{ $ordersCount }} órdenes</span>
                </a>

                <a href="{{ route('documents.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Documentos</span>
                    <span class="dashboard-link-text">Ver y administrar documentos comerciales</span>
                    <span class="dashboard-link-meta">{{ $documentsCount }} documentos</span>
                </a>
            </div>
        </x-card>
    </x-page>
@endsection