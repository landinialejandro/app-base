{{-- FILE: resources/views/welcome.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'app-base')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header title="Panel de acceso" vertical="vertical">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            Ir al dashboard
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="btn btn-secondary">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            Ingresar
                        </a>
                    @endauth
                </x-page-header>
            </div>
        </div>
    </x-page>
@endsection