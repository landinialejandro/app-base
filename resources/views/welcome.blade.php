{{-- FILE: resources/views/welcome.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'app-base')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel">
                <x-page-header title="app-base" vertical="vertical">
                    <p>Base SaaS multi-tenant para gestión comercial y operativa.</p>

                    @auth
                        @if (auth()->user()->is_superadmin)
                            <x-button-primary :href="route('admin.dashboard')">
                                Ir a administración
                            </x-button-primary>
                        @else
                            <x-button-primary :href="route('dashboard')">
                                Ir al dashboard
                            </x-button-primary>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="btn btn-secondary">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <div class="form-actions">
                            <x-button-create :href="route('public.signup-requests.create')" label="Solicitar una empresa" />

                            <x-button-secondary :href="route('login')">
                                Ingresar
                            </x-button-secondary>
                        </div>
                    @endauth
                </x-page-header>
            </div>
        </div>
    </x-page>
@endsection
