{{-- FILE: resources/views/public/signup-requests/create.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Solicitar empresa')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--md">
                <x-page-header title="Solicitar una empresa" vertical="vertical">
                    <p class="public-text">
                        Completa estos datos para iniciar el alta de tu empresa.
                    </p>
                    <p class="public-text">
                        Luego podremos revisar contigo la configuración inicial.
                    </p>
                </x-page-header>

                <x-card>
                    <form method="POST" action="{{ route('public.signup-requests.store') }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label for="requested_name" class="form-label">Nombre</label>
                            <input id="requested_name" name="requested_name" type="text"
                                class="form-control @error('requested_name') is-invalid @enderror"
                                value="{{ old('requested_name') }}" placeholder="Tu nombre" required>
                            <div class="form-help">
                                Escribe tu nombre para poder contactarte.
                            </div>
                            @error('requested_name')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="requested_email" class="form-label">Email</label>
                            <input id="requested_email" name="requested_email" type="email"
                                class="form-control @error('requested_email') is-invalid @enderror"
                                value="{{ old('requested_email') }}" placeholder="tunombre@empresa.com" required>
                            <div class="form-help">
                                Ingresa un correo válido. Lo usaremos para enviarte el acceso inicial.
                            </div>
                            @error('requested_email')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="company_name" class="form-label">Empresa</label>
                            <input id="company_name" name="company_name" type="text"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name') }}" placeholder="Nombre de tu empresa" required>
                            <div class="form-help">
                                Escribe el nombre de tu empresa. No te preocupes, luego lo podrás modificar.
                            </div>
                            @error('company_name')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone_whatsapp" class="form-label">Teléfono / WhatsApp</label>
                            <input id="phone_whatsapp" name="phone_whatsapp" type="text"
                                class="form-control @error('phone_whatsapp') is-invalid @enderror"
                                value="{{ old('phone_whatsapp') }}" placeholder="+5492991234567" required>
                            <div class="form-help">
                                Ingresa el número en formato internacional, por ejemplo +5492991234567.
                            </div>
                            @error('phone_whatsapp')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Enviar solicitud
                            </button>

                            <a href="{{ url('/') }}" class="btn btn-secondary">
                                Volver
                            </a>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </x-page>
@endsection