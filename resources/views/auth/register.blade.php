{{-- FILE: resources/views/auth/register.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Registro')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header title="Crear cuenta" vertical="vertical" />

                <x-card>
                    <form method="POST" action="{{ route('register') }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="name">Nombre</label>
                            <input id="name" class="form-control" name="name" type="text" value="{{ old('name') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input id="password" class="form-control" name="password" type="password" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                            <input id="password_confirmation" class="form-control" name="password_confirmation" type="password" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Crear cuenta
                            </button>

                            <a href="{{ route('login') }}" class="btn btn-secondary">
                                Volver al login
                            </a>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection
