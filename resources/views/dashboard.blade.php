@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page>

        <x-page-header title="Dashboard" />

        <x-card>
            <div class="dashboard-intro">
                <h2 class="dashboard-section-title">Empresa actual</h2>

                <div class="detail-list">
                    <div class="detail-label">Empresa</div>
                    <div class="detail-value">{{ $tenant->name }}</div>

                    <div class="detail-label">Slug</div>
                    <div class="detail-value">{{ $tenant->slug }}</div>

                    <div class="detail-label">Proyectos</div>
                    <div class="detail-value">{{ $projectsCount }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <h2 class="dashboard-section-title">Accesos rápidos</h2>

            <div class="dashboard-grid">
                <a href="{{ route('projects.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Proyectos</span>
                    <span class="dashboard-link-text">Ver y administrar proyectos</span>
                </a>

                <a href="{{ route('parties.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Contactos</span>
                    <span class="dashboard-link-text">Ver y administrar contactos</span>
                </a>
            </div>
        </x-card>

    </x-page>
@endsection