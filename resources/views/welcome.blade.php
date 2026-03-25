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
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                                Ir a administración
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Ir al dashboard
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="btn btn-secondary">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <div class="form-actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="btn btn-success">
                                Solicitar una empresa
                            </a>

                            <a href="{{ route('login') }}" class="btn btn-secondary">
                                Ingresar
                            </a>
                        </div>
                    @endauth
                </x-page-header>
            </div>
        </div>
    </x-page>
@endsection
