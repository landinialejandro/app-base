{{-- FILE: resources/views/tenants/profile.blade.php --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    @php
        $settings = $tenant->settings ?? [];
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

        <x-card>
            <form method="POST" action="{{ route('tenant.profile.update') }}" class="form">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <h2 class="section-title">Identificación</h2>

                    <div class="detail-grid detail-grid--2">
                        <div class="form-group">
                            <label for="name" class="form-label">Nombre visible</label>
                            <input id="name" name="name" type="text" class="form-control"
                                value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="legal_name" class="form-label">Razón social</label>
                            <input id="legal_name" name="settings[legal_name]" type="text" class="form-control"
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

                    <div class="detail-grid detail-grid--2">
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

                    <div class="detail-grid detail-grid--2">
                        <div class="form-group">
                            <label for="company_address" class="form-label">Dirección</label>
                            <input id="company_address" name="settings[address]" type="text" class="form-control"
                                value="{{ old('settings.address', $settings['address'] ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="company_city" class="form-label">Ciudad</label>
                            <input id="company_city" name="settings[city]" type="text" class="form-control"
                                value="{{ old('settings.city', $settings['city'] ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="company_state" class="form-label">Provincia / Estado</label>
                            <input id="company_state" name="settings[state]" type="text" class="form-control"
                                value="{{ old('settings.state', $settings['state'] ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="company_country" class="form-label">País</label>
                            <input id="company_country" name="settings[country]" type="text" class="form-control"
                                value="{{ old('settings.country', $settings['country'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title">Datos técnicos</h2>

                    <div class="detail-grid detail-grid--2">
                        <div class="detail-item">
                            <span class="detail-label">Slug</span>
                            <span class="detail-value">{{ $tenant->slug }}</span>
                            <div class="form-help">
                                Identificador interno no editable desde esta pantalla.
                            </div>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">ID</span>
                            <span class="detail-value">{{ $tenant->id }}</span>
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
    </x-page>
@endsection
