{{-- FILE: resources/views/self-service-sales/thanks.blade.php | V2 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Registro recibido')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header
                    title="Registro recibido"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    <p>
                        Recibimos tu solicitud de registro como cliente.
                    </p>

                    @if($email)
                        <p>
                            Vamos a utilizar el email <strong>{{ $email }}</strong> para continuar la validación.
                        </p>
                    @endif

                    <p>
                        El siguiente paso es confirmar tu email. Para operar en la tienda, luego deberás completar tus datos reales de identidad.
                    </p>

                    <div class="form-actions">
                        <a href="{{ route('self_service_sales.shop', ['tenant' => $tenant]) }}" class="btn btn-primary">
                            Volver a la tienda
                        </a>
                    </div>
                </x-card>

                <x-dev-component-version name="self-service-sales.thanks" version="2" align="right" />
            </div>
        </div>
    </x-page>
@endsection