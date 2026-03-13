{{-- FILE: resources/views/auth/login.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header title="Ingresar" vertical="vertical" />

                <x-card>
                    <form method="POST" action="{{ route('login') }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}"
                                required autofocus autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input id="password" class="form-control" name="password" type="password" required
                                autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="remember">
                                <input id="remember" class="form-checkbox" type="checkbox" name="remember" value="1">
                                Recordarme
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Entrar
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection