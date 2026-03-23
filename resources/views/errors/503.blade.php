{{-- FILE: resources/views/errors/503.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Servicio no disponible')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Servicio no disponible" vertical="vertical" />

                <x-card>
                    <p class="public-text">La aplicación no está disponible temporalmente.</p>
                    <p class="public-text">Puede tratarse de una tarea de mantenimiento o de una interrupción momentánea.</p>

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
