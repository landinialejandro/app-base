{{-- FILE: resources/views/self-service-sales/access.blade.php | V2 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Tienda')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header
                    title="Ingresar a Tienda"
                    subtitle="Acceso para clientes"
                    vertical="vertical"
                />

                <x-card>
                    @if (session('success'))
                        <div class="form-help" style="margin-bottom: 1rem;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="form-help is-error" style="margin-bottom: 1rem;">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('self_service_sales.access.store') }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input
                                id="email"
                                class="form-control"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input
                                id="password"
                                class="form-control"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Entrar
                            </button>

                            <a href="{{ route('landing.home') }}" class="btn btn-secondary">
                                Volver
                            </a>
                        </div>
                    </form>
                </x-card>

                <x-card>
                    <p class="mb-0">
                        Si todavía no tenés acceso, ingresá a la tienda de la empresa donde querés registrarte.
                        Si olvidaste tu contraseña, vas a poder recuperar el acceso cuando esté habilitado el login externo de clientes.
                    </p>
                </x-card>

                <x-dev-component-version name="self-service-sales.access" version="V2" align="right" />
            </div>
        </div>
    </x-page>
@endsection