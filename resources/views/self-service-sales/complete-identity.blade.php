{{-- FILE: resources/views/self-service-sales/complete-identity.blade.php | V2 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Finalizar registro')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header
                    title="Finalizar registro"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    <p>
                        Completá tus datos y creá una contraseña para poder volver a ingresar a esta tienda.
                    </p>

                    <p class="form-help">
                        Este paso no habilita automáticamente la operación comercial. La tienda deberá revisar tu
                        habilitación antes de permitir compras, saldo, fichas, QR, pagos o movimientos comerciales reales.
                    </p>

                    <form method="POST" action="{{ route('self_service_sales.identity.update', ['tenant' => $tenant]) }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="name">Nombre completo</label>
                            <input
                                id="name"
                                class="form-control"
                                name="name"
                                type="text"
                                value="{{ old('name', $party->name) }}"
                                required
                            >
                            @error('name')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="document_type">Tipo de documento</label>
                            <select
                                id="document_type"
                                class="form-control"
                                name="document_type"
                                required
                            >
                                <option value="">Seleccionar</option>
                                <option value="dni" @selected(old('document_type', $party->document_type) === 'dni')>DNI</option>
                                <option value="passport" @selected(old('document_type', $party->document_type) === 'passport')>Pasaporte</option>
                                <option value="other" @selected(old('document_type', $party->document_type) === 'other')>Otro</option>
                            </select>
                            @error('document_type')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="document_number">Número de documento</label>
                            <input
                                id="document_number"
                                class="form-control"
                                name="document_number"
                                type="text"
                                value="{{ old('document_number', $party->document_number) }}"
                                required
                            >
                            @error('document_number')
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
                                value="{{ old('phone', $party->phone) }}"
                                required
                            >
                            @error('phone')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input
                                id="password"
                                class="form-control"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                            >
                            @error('password')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                            <input
                                id="password_confirmation"
                                class="form-control"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-check">
                                <input
                                    type="checkbox"
                                    name="terms_accepted"
                                    value="1"
                                    required
                                    @checked(old('terms_accepted'))
                                >
                                <span>
                                    Declaro que los datos ingresados son correctos y acepto que la tienda los revise para habilitar la operación comercial.
                                </span>
                            </label>
                            @error('terms_accepted')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Finalizar registro
                            </button>
                        </div>
                    </form>
                </x-card>

                <x-dev-component-version name="self-service-sales.complete-identity" version="V2" align="right" />
            </div>
        </div>
    </x-page>
@endsection