{{-- FILE: resources/views/self-service-sales/shop.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Tienda')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel">
                <x-page-header
                    title="Tienda"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    <p>
                        Esta es la tienda autogestiva de {{ $tenant->name }}.
                    </p>

                    <p>
                        Desde aquí vas a poder registrarte para comprar saldo, consultar movimientos y acceder a los servicios habilitados por la tienda.
                    </p>

                    <div class="form-actions">
                        <a href="{{ route('self_service_sales.register.create', ['tenant' => $tenant]) }}" class="btn btn-primary">
                            Registrarme como cliente
                        </a>
                    </div>
                </x-card>

                <x-dev-component-version name="self-service-sales.shop" version="1" align="right" />
            </div>
        </div>
    </x-page>
@endsection