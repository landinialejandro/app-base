{{-- FILE: resources/views/public/signup-requests/thank-you.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Solicitud enviada')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header title="Solicitud enviada" vertical="vertical">
                    <p>Tu solicitud fue registrada correctamente.</p>
                    <p>La revisaremos para continuar con el proceso de alta.</p>

                    <div class="form-actions">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            Volver al inicio
                        </a>

                        <a href="{{ route('login') }}" class="btn btn-secondary">
                            Ingresar
                        </a>
                    </div>
                </x-page-header>
            </div>
        </div>
    </x-page>
@endsection