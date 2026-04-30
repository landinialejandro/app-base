{{-- FILE: resources/views/profile/show.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
    @php
        $isSuperadmin = auth()->user()->is_superadmin;
        $homeRoute = $isSuperadmin ? route('admin.dashboard') : route('dashboard');
    @endphp

    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => $homeRoute], ['label' => 'Perfil']]" />

        <x-page-header title="Perfil" />

        <x-card>
            <form method="POST" action="{{ url('/user/profile-information') }}" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="form-label">Nombre</label>
                    <input id="name" name="name" type="text" class="form-control"
                        value="{{ old('name', auth()->user()->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email" type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>

                    <div class="form-help">
                        Este correo identifica tu acceso al sistema.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    <a href="{{ $homeRoute }}" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </form>

            @if (!$isSuperadmin)
                <hr class="hr-muted">

                <form method="POST" action="{{ route('profile.party') }}" class="form">
                    @csrf

                    <div class="form-actions">
                        <button type="submit" class="btn btn-secondary">
                            Más datos
                        </button>
                    </div>
                </form>
            @endif
        </x-card>
        <x-dev-component-version name="profile.show" version="V2" />
    </x-page>
@endsection
