{{-- FILE: resources/views/errors/500.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Error interno')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Error interno" vertical="vertical" />

                <x-card>
                    <p class="public-text">Ocurrió un problema inesperado al procesar la solicitud.</p>
                    <p class="public-text">Inténtalo nuevamente en unos minutos.</p>

                    <div class="public-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Ir al inicio
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                Ir al login
                            </a>
                        @endauth
                    </div>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection
