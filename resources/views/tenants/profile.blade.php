{{-- FILE: resources/views/tenants/profile.blade.php --}}

@extends('layouts.app')

@section('title', 'Perfil de empresa')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Perfil de empresa'],
        ]" />

        <x-page-header title="Perfil de empresa" />

        <x-card>
            <form method="POST" action="{{ route('tenant.profile.update') }}" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="form-label">Nombre</label>
                    <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $tenant->name) }}"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <div class="detail-value">{{ $tenant->slug }}</div>
                    <div class="form-help">
                        El identificador interno no es editable desde esta pantalla.
                    </div>
                </div>


                <div class="form-group">
                    <label class="form-label">ID</label>
                    <div class="detail-value">{{ $tenant->id }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Configuración</label>
                    <div class="detail-value">
                        @if (!empty($tenant->settings))
                            <pre
                                class="tenant-settings-json">{{ json_encode($tenant->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            —
                        @endif
                    </div>
                    <div class="form-help">
                        La configuración avanzada todavía no es editable desde esta pantalla.
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