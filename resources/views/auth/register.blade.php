@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Registro')

@section('content')
    <x-page>

        <div class="welcome-page">

            <div style="width:400px;max-width:100%;">

                <x-page-header title="Crear cuenta" vertical="vertical" />

                <x-card>

                    @if ($errors->any())
                        <div class="alert alert-error">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="/register">
                        @csrf

                        <div class="form-group">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="name" type="text" value="{{ old('name') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input class="form-control" name="password" type="password" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirmar password</label>
                            <input class="form-control" name="password_confirmation" type="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Crear cuenta
                        </button>

                    </form>

                    <p style="margin-top:var(--space-3);">
                        <a href="/login">Volver al login</a>
                    </p>

                </x-card>

            </div>

        </div>

    </x-page>
@endsection