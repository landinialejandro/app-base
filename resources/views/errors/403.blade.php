{{-- FILE: resources/views/errors/403.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Acceso denegado')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Acceso denegado" vertical="vertical" />

                <x-card>
                    <p class="public-text">No tienes permisos para acceder a esta sección.</p>
                    <p class="public-text">Si crees que se trata de un error, consulta con la persona administradora.</p>

                    <div class="public-actions">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            Ir al inicio
                        </a>
                    </div>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection
