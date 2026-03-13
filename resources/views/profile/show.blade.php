{{-- FILE: resources/views/profile/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Perfil'],
        ]" />

        <x-page-header title="Perfil" />

        <x-card>
            <form method="POST" action="{{ url('/user/profile-information') }}" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="form-label">Nombre</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control"
                        value="{{ old('name', auth()->user()->name) }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control"
                        value="{{ old('email', auth()->user()->email) }}"
                        required
                    >
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
