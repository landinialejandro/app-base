{{-- FILE: resources/views/errors/419.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Sesión expirada')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Sesión expirada" vertical="vertical" />

                <x-card>
                    <p class="public-text">La sesión o el formulario expiró por inactividad.</p>
                    <p class="public-text">Vuelve a ingresar para continuar.</p>

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
