{{-- FILE: resources/views/invitations/show.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Invitación')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--lg">
                <x-page-header title="Invitación" vertical="vertical" />

                <x-card>
                    @if ($state === 'accepted')
                        <p class="public-text">Esta invitación ya fue aceptada.</p>
                    @elseif ($state === 'expired')
                        <p class="public-text">Esta invitación está vencida.</p>
                    @else
                        <p class="public-text">La invitación para <strong>{{ $invitation->email }}</strong> es válida.</p>

                        <p class="public-text">
                            Tipo de invitación:
                            <strong>{{ $invitation->type }}</strong>
                        </p>

                        @if ($invitation->type === 'owner_signup' && $invitation->signupRequest)
                            <p class="public-text">
                                Empresa:
                                <strong>{{ $invitation->signupRequest->company_name }}</strong>
                            </p>
                        @endif

                        @if ($mustLogin)
                            <p class="public-text">
                                Ya existe una cuenta con este email. Para continuar, inicia sesión con el email invitado.
                            </p>

                            <div class="public-actions">
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    Iniciar sesión
                                </a>
                            </div>
                        @else
                            <p class="public-text">
                                Completa tus datos para finalizar el alta.
                            </p>

                            <form method="POST" action="{{ route('invitation.accept.store', $invitation->token) }}"
                                class="form public-actions">
                                @csrf

                                <div class="form-group">
                                    <label class="form-label" for="name">Nombre</label>
                                    <input id="name" class="form-control" name="name" type="text"
                                        value="{{ old('name', $prefillUser->name ?? $invitation->signupRequest->requested_name ?? '') }}"
                                        required>
                                </div>

                                @if (!$emailExists)
                                    <div class="form-group">
                                        <label class="form-label" for="password">Contraseña</label>
                                        <input id="password" class="form-control" name="password" type="password" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                                        <input id="password_confirmation" class="form-control" name="password_confirmation"
                                            type="password" required>
                                    </div>
                                @else
                                    <p class="public-text">
                                        Se usarán tus datos de cuenta existentes para continuar con esta invitación.
                                    </p>
                                @endif

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        {{ $invitation->type === 'owner_signup' ? 'Crear empresa y continuar' : 'Finalizar alta' }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </x-card>
            </div>
        </div>
    </x-page>
@endsection
