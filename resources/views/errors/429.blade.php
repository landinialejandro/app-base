{{-- FILE: resources/views/errors/429.blade.php | V1 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Demasiados intentos')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Demasiados intentos" vertical="vertical" />

                <x-card>
                    <p class="public-text">Has realizado demasiadas solicitudes en poco tiempo.</p>
                    <p class="public-text">Espera unos minutos e inténtalo nuevamente.</p>

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
