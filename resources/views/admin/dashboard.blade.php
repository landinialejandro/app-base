{{-- FILE: resources/views/admin/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Superadmin')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Administración'],
        ]" />

        <x-page-header title="Panel de superadmin">
            <div class="form-actions">
                <a href="{{ route('profile.show') }}" class="btn btn-secondary">
                    Mi perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="btn btn-secondary">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </x-page-header>

        <x-card>
            <p>Acceso global del sistema.</p>
            <p>Desde aquí se administrará el onboarding y la capa pública.</p>
        </x-card>
    </x-page>
@endsection