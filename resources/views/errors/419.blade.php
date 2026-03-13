{{-- FILE: resources/views/errors/419.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Sesión expirada')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div style="width: 520px; max-width: 100%;">
                <x-page-header title="Sesión expirada" vertical="vertical" />

                <x-card>
                    <p>La sesión o el formulario expiró por inactividad.</p>

                    <p style="margin-top: var(--space-2);">
                        Vuelve a ingresar para continuar.
                    </p>

                    <div style="margin-top: var(--space-3);">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            Ir al login
                        </a>
                    </div>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection