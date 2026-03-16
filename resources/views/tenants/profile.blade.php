{{-- FILE: resources/views/tenants/profile.blade.php --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    @php
        $settings = $tenant->settings ?? [];
        $activeTab = $activeTab ?? 'general';
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Perfil de empresa']]" />

        <x-page-header title="Perfil de empresa" />

        <div class="summary-inline-grid mb-3">
            <div class="summary-inline-item">
                <span class="summary-inline-label">Empresa</span>
                <span class="summary-inline-value">{{ $tenant->name }}</span>
            </div>

            <div class="summary-inline-item">
                <span class="summary-inline-label">Slug</span>
                <span class="summary-inline-value">{{ $tenant->slug }}</span>
            </div>

            <div class="summary-inline-item">
                <span class="summary-inline-label">ID</span>
                <span class="summary-inline-value">{{ $tenant->id }}</span>
            </div>
        </div>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Perfil de empresa">
                <button type="button" class="tabs-link {{ $activeTab === 'general' ? 'is-active' : '' }}"
                    data-tab-link="general" role="tab"
                    aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                    General
                </button>

                <button type="button" class="tabs-link {{ $activeTab === 'users' ? 'is-active' : '' }}"
                    data-tab-link="users" role="tab" aria-selected="{{ $activeTab === 'users' ? 'true' : 'false' }}">
                    Usuarios y accesos
                </button>
            </div>

            <section class="tab-panel {{ $activeTab === 'general' ? 'is-active' : '' }}" data-tab-panel="general"
                {{ $activeTab === 'general' ? '' : 'hidden' }}>
                <div class="tab-panel-stack">
                    <x-card>
                        <form method="POST" action="{{ route('tenant.profile.update') }}" class="form">
                            @csrf
                            @method('PUT')

                            <div class="form-section">
                                <h2 class="section-title">Identificación</h2>

                                <div class="detail-grid">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Nombre visible</label>
                                        <input id="name" name="name" type="text" class="form-control"
                                            value="{{ old('name', $tenant->name) }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="legal_name" class="form-label">Razón social</label>
                                        <input id="legal_name" name="settings[legal_name]" type="text"
                                            class="form-control"
                                            value="{{ old('settings.legal_name', $settings['legal_name'] ?? '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="tax_id" class="form-label">CUIT / ID fiscal</label>
                                        <input id="tax_id" name="settings[tax_id]" type="text" class="form-control"
                                            value="{{ old('settings.tax_id', $settings['tax_id'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h2 class="section-title">Contacto</h2>

                                <div class="detail-grid">
                                    <div class="form-group">
                                        <label for="company_email" class="form-label">Correo principal</label>
                                        <input id="company_email" name="settings[email]" type="email" class="form-control"
                                            value="{{ old('settings.email', $settings['email'] ?? '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="company_phone" class="form-label">Teléfono</label>
                                        <input id="company_phone" name="settings[phone]" type="text" class="form-control"
                                            value="{{ old('settings.phone', $settings['phone'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h2 class="section-title">Ubicación</h2>

                                <div class="detail-grid">
                                    <div class="form-group">
                                        <label for="company_address" class="form-label">Dirección</label>
                                        <input id="company_address" name="settings[address]" type="text"
                                            class="form-control"
                                            value="{{ old('settings.address', $settings['address'] ?? '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="company_city" class="form-label">Ciudad</label>
                                        <input id="company_city" name="settings[city]" type="text" class="form-control"
                                            value="{{ old('settings.city', $settings['city'] ?? '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="company_state" class="form-label">Provincia / Estado</label>
                                        <input id="company_state" name="settings[state]" type="text"
                                            class="form-control"
                                            value="{{ old('settings.state', $settings['state'] ?? '') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="company_country" class="form-label">País</label>
                                        <input id="company_country" name="settings[country]" type="text"
                                            class="form-control"
                                            value="{{ old('settings.country', $settings['country'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h2 class="section-title">Datos técnicos</h2>

                                <div class="detail-grid">
                                    <div class="detail-block">
                                        <span class="detail-block-label">Slug</span>
                                        <div class="detail-block-value">{{ $tenant->slug }}</div>
                                        <div class="form-help">Identificador interno no editable desde esta pantalla.</div>
                                    </div>

                                    <div class="detail-block">
                                        <span class="detail-block-label">ID</span>
                                        <div class="detail-block-value">{{ $tenant->id }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    Guardar cambios
                                </button>

                                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                    Volver
                                </a>
                            </div>
                        </form>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel {{ $activeTab === 'users' ? 'is-active' : '' }}" data-tab-panel="users"
                {{ $activeTab === 'users' ? '' : 'hidden' }}>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Usuarios del tenant</h2>
                            <p class="dashboard-section-text">
                                Gestión básica de acceso por empresa. El bloqueo afecta solo a este tenant.
                            </p>
                        </div>

                        @if ($memberships->count())
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Email</th>
                                            <th>Owner</th>
                                            <th>Roles</th>
                                            <th>Estado</th>
                                            <th>Alta</th>
                                            <th class="compact-actions-cell"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($memberships as $membership)
                                            <tr>
                                                <td>{{ $membership->user?->name ?? '—' }}</td>
                                                <td>{{ $membership->user?->email ?? '—' }}</td>
                                                <td>
                                                    @if ($membership->is_owner)
                                                        <span class="status-badge status-badge--done">Sí</span>
                                                    @else
                                                        <span class="helper-inline">No</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($membership->roles->count())
                                                        @foreach ($membership->roles as $role)
                                                            <div>{{ $role->name }}</div>
                                                        @endforeach
                                                    @else
                                                        <span class="helper-inline">Sin roles</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($membership->status === 'blocked')
                                                        <span class="status-badge status-badge--cancelled">Bloqueado</span>
                                                    @else
                                                        <span class="status-badge status-badge--done">Activo</span>
                                                    @endif
                                                </td>
                                                <td>{{ $membership->joined_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td class="compact-actions-cell">
                                                    @if ($membership->is_owner)
                                                        <span class="helper-inline">Owner</span>
                                                    @elseif ($membership->status === 'blocked')
                                                        <form method="POST"
                                                            action="{{ route('tenant.memberships.unblock', $membership) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary">
                                                                Rehabilitar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST"
                                                            action="{{ route('tenant.memberships.block', $membership) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary">
                                                                Bloquear
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">No hay usuarios asociados a esta empresa.</p>
                        @endif
                    </x-card>
                </div>
            </section>
        </div>
    </x-page>
@endsection
