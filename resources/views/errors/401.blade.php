{{-- FILE: resources/views/errors/401.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Autenticación requerida')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Autenticación requerida" vertical="vertical" />

                <x-card>
                    <p class="public-text">Necesitas iniciar sesión para acceder a esta sección.</p>
                    <p class="public-text">Ingresa con tu cuenta para continuar.</p>

                    <div class="public-actions">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            Ir al login
                        </a>
                    </div>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection
