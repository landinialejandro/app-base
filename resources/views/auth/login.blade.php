@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <x-page>

        <div class="welcome-page">

            <div style="width:400px;max-width:100%;">

                <x-page-header title="Ingresar" vertical="vertical" />

                <x-card>

                    @if ($errors->any())
                        <div class="alert alert-error">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" value="{{ old('email') }}" required
                                autofocus>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input class="form-control" name="password" type="password" required>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="remember" value="1">
                                Recordarme
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Entrar
                        </button>

                    </form>

                    <p style="margin-top:var(--space-3);">
                        <a href="/register">Crear cuenta</a>
                    </p>

                </x-card>

            </div>

        </div>

    </x-page>
@endsection