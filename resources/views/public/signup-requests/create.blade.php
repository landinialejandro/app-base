{{-- FILE: resources/views/public/signup-requests/create.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Solicitar empresa')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--sm">
                <x-page-header title="Solicitar una empresa" vertical="vertical">
                    <p>Completa estos datos y nos contactaremos para iniciar el alta.</p>
                </x-page-header>

                <x-card>
                    <form method="POST" action="{{ route('public.signup-requests.store') }}" class="form">
                        @csrf

                        <div class="form-group">
                            <label for="requested_name" class="form-label">Nombre</label>
                            <input id="requested_name" name="requested_name" type="text" class="form-control"
                                value="{{ old('requested_name') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="requested_email" class="form-label">Email</label>
                            <input id="requested_email" name="requested_email" type="email" class="form-control"
                                value="{{ old('requested_email') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="company_name" class="form-label">Empresa</label>
                            <input id="company_name" name="company_name" type="text" class="form-control"
                                value="{{ old('company_name') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="phone_whatsapp" class="form-label">Teléfono / WhatsApp</label>
                            <input id="phone_whatsapp" name="phone_whatsapp" type="text" class="form-control"
                                value="{{ old('phone_whatsapp') }}" required>
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