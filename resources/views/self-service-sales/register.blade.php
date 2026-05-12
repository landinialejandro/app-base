{{-- FILE: resources/views/self-service-sales/register.blade.php | V2 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Registro de cliente')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header
                    title="Registro de cliente"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    <form method="POST" action="{{ route('self_service_sales.register.store', ['tenant' => $tenant]) }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="display_name">Nombre para mostrar</label>
                            <input
                                id="display_name"
                                class="form-control"
                                name="display_name"
                                type="text"
                                value="{{ old('display_name') }}"
                                required
                            >
                            @error('display_name')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input
                                id="email"
                                class="form-control"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                            >
                            @error('email')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Teléfono</label>
                            <input
                                id="phone"
                                class="form-control"
                                name="phone"
                                type="text"
                                value="{{ old('phone') }}"
                                required
                            >
                            @error('phone')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <p class="form-help">
                            Este registro confirma tu email como primer paso. Para operar en la tienda, más adelante vas a tener que completar tus datos reales de identidad.
                        </p>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Registrarme
                            </button>

                            <a href="{{ route('self_service_sales.shop', ['tenant' => $tenant]) }}" class="btn btn-secondary">
                                Volver a la tienda
                            </a>
                        </div>
                    </form>
                </x-card>

                <x-dev-component-version name="self-service-sales.register" version="2" align="right" />
            </div>
        </div>
    </x-page>
@endsection