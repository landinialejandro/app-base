{{-- FILE: resources/views/errors/404.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Página no encontrada')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Página no encontrada" vertical="vertical" />

                <x-card>
                    <p class="public-text">La página o el recurso que buscas no existe o ya no está disponible.</p>
                    <p class="public-text">Revisa la dirección o vuelve al inicio para continuar.</p>

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
