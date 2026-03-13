{{-- resources/views/auth/login.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div style="width:400px;max-width:100%;">
                <x-page-header title="Ingresar" vertical="vertical" />

                <x-card>
                    @if (session('error'))
                        <div class="alert alert-error">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}"
                                required autofocus autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input id="password" class="form-control" name="password" type="password" required
                                autocomplete="current-password">
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
                </x-card>
            </div>
        </div>
    </x-page>
@endsection