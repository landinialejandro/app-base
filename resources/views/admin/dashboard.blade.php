{{-- FILE: resources/views/admin/dashboard.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Superadmin')

@section('content')
    <x-page>
        <x-breadcrumb :items="[['label' => 'Administración']]" />

        <x-page-header title="Panel de superadmin">
            <div class="page-actions">
                <a href="{{ route('profile.show') }}" class="btn btn-secondary">
                    Mi perfil
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </x-page-header>

        <x-card>
            <div class="dashboard-tenant-summary">
                <div class="dashboard-tenant-main">
                    <span class="dashboard-tenant-label">Vista global</span>
                    <h2 class="dashboard-tenant-name">Administración del sistema</h2>
                    <p class="dashboard-tenant-slug">Capa pública, onboarding y control general</p>
                </div>

                <div class="dashboard-tenant-stats">
                    <span class="dashboard-tenant-stat">{{ $tenantsCount }} tenants</span>
                    <span class="dashboard-tenant-stat">{{ $usersCount }} usuarios</span>
                    <span class="dashboard-tenant-stat">{{ $pendingSignupRequestsCount }} solicitudes pendientes</span>
                    <span class="dashboard-tenant-stat">{{ $pendingOwnerInvitationsCount }} invitaciones owner
                        pendientes</span>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Onboarding</h2>
                <p class="dashboard-section-text">Seguimiento de solicitudes, aprobaciones e invitaciones iniciales.</p>
            </div>

            <div class="dashboard-grid">
                <a href="{{ route('admin.signup-requests.index') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Solicitudes pendientes</span>
                    <span class="dashboard-link-text">Revisar y procesar solicitudes de nuevas empresas</span>
                    <span class="dashboard-link-meta">{{ $pendingSignupRequestsCount }} pendientes</span>
                </a>

                <a href="{{ route('admin.signup-requests.processed') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Solicitudes procesadas</span>
                    <span class="dashboard-link-text">Consultar solicitudes aprobadas y rechazadas</span>
                    <span class="dashboard-link-meta">{{ $ownerInvitationsToSendCount }} listas para envío</span>
                </a>

                <a href="{{ route('admin.invitations.owner-signups') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Invitaciones owner signup</span>
                    <span class="dashboard-link-text">Ver invitaciones generadas desde solicitudes aprobadas</span>
                    <span class="dashboard-link-meta">{{ $pendingOwnerInvitationsCount }} enviadas pendientes del
                        owner</span>
                </a>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Estructura global</h2>
                <p class="dashboard-section-text">
                    Resumen de owners, usuarios compartidos y composición actual de tenants.
                </p>
            </div>

            <div class="dashboard-grid">
                <a href="{{ route('admin.metrics.owners') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Owners activos</span>
                    <span class="dashboard-link-text">Usuarios con al menos una empresa en rol owner</span>
                    <span class="dashboard-link-meta">{{ $ownersCount }} owners</span>
                </a>

                <a href="#" class="dashboard-link-card">
                    <span class="dashboard-link-title">Owners multiempresa</span>
                    <span class="dashboard-link-text">Owners que pertenecen como owner a más de un tenant</span>
                    <span class="dashboard-link-meta">{{ $multiTenantOwnersCount }} owners</span>
                </a>

                <a href="#" class="dashboard-link-card">
                    <span class="dashboard-link-title">Usuarios compartidos</span>
                    <span class="dashboard-link-text">Usuarios que pertenecen a más de un tenant</span>
                    <span class="dashboard-link-meta">{{ $sharedUsersCount }} usuarios</span>
                </a>

                <a href="#" class="dashboard-link-card">
                    <span class="dashboard-link-title">Tenants individuales</span>
                    <span class="dashboard-link-text">Tenants con un solo usuario asociado</span>
                    <span class="dashboard-link-meta">{{ $singleUserTenantsCount }} tenants</span>
                </a>

                <a href="{{ route('admin.metrics.tenants') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Tenants colaborativos</span>
                    <span class="dashboard-link-text">Ver tenants y cantidad de usuarios asociados</span>
                    <span class="dashboard-link-meta">{{ $multiUserTenantsCount }} tenants con más de un usuario</span>
                </a>

                <a href="#" class="dashboard-link-card">
                    <span class="dashboard-link-title">Promedio de usuarios</span>
                    <span class="dashboard-link-text">Promedio actual de usuarios por tenant</span>
                    <span class="dashboard-link-meta">{{ $averageUsersPerTenant }} por tenant</span>
                </a>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Sistema y verificación</h2>
                <p class="dashboard-section-text">Accesos rápidos a vistas públicas y control general.</p>
            </div>

            <div class="dashboard-grid">
                <a href="{{ url('/') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Inicio público</span>
                    <span class="dashboard-link-text">Ver la landing pública actual del sistema</span>
                    <span class="dashboard-link-meta">Pantalla pública</span>
                </a>

                <a href="{{ route('login') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Login</span>
                    <span class="dashboard-link-text">Verificar acceso y presentación de autenticación</span>
                    <span class="dashboard-link-meta">Pantalla pública</span>
                </a>

                <a href="{{ route('public.signup-requests.create') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Solicitar empresa</span>
                    <span class="dashboard-link-text">Verificar formulario público de alta</span>
                    <span class="dashboard-link-meta">Pantalla pública</span>
                </a>

                <a href="{{ route('public.signup-requests.thank-you') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Solicitud enviada</span>
                    <span class="dashboard-link-text">Verificar confirmación pública posterior al envío</span>
                    <span class="dashboard-link-meta">Pantalla pública</span>
                </a>

                <a href="{{ route('profile.show') }}" class="dashboard-link-card">
                    <span class="dashboard-link-title">Mi perfil</span>
                    <span class="dashboard-link-text">Editar datos del usuario superadmin</span>
                    <span class="dashboard-link-meta">Vista interna</span>
                </a>
            </div>
        </x-card>
    </x-page>
@endsection
