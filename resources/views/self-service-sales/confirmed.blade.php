{{-- FILE: resources/views/self-service-sales/confirmed.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Registro confirmado')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header
                    title="Registro confirmado"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    <p>
                        Tu registro como cliente fue confirmado correctamente.
                    </p>

                    <p>
                        Ya quedaste registrado/a en la tienda como cliente.
                    </p>

                    @if($party)
                        <div class="detail-block">
                            <strong>Cliente:</strong>
                            {{ $party->display_name ?: $party->name }}
                        </div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ route('self_service_sales.shop', ['tenant' => $tenant]) }}" class="btn btn-primary">
                            Volver a la tienda
                        </a>
                    </div>
                </x-card>

                <x-dev-component-version name="self-service-sales.confirmed" version="1" align="right" />
            </div>
        </div>
    </x-page>
@endsection