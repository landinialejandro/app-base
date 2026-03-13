{{-- FILE: resources/views/invitations/show.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Invitación')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div style="width: 560px; max-width: 100%;">
                <x-page-header title="Invitación" vertical="vertical" />

                <x-card>
                    @if ($state === 'accepted')
                        <p>Esta invitación ya fue aceptada.</p>

                    @elseif ($state === 'expired')
                        <p>Esta invitación está vencida.</p>

                    @else
                        <p>La invitación para <strong>{{ $invitation->email }}</strong> es válida.</p>

                        <p style="margin-top: var(--space-2);">
                            Tipo de invitación:
                            <strong>{{ $invitation->type }}</strong>
                        </p>

                        @if ($invitation->type === 'owner_signup' && $invitation->signupRequest)
                            <p style="margin-top: var(--space-2);">
                                Empresa:
                                <strong>{{ $invitation->signupRequest->company_name }}</strong>
                            </p>
                        @endif

                        @if ($mustLogin)
                            <p style="margin-top: var(--space-2);">
                                Ya existe una cuenta con este email. Para continuar, inicia sesión con el email invitado.
                            </p>

                            <div style="margin-top: var(--space-3);">
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    Iniciar sesión
                                </a>
                            </div>
                        @else
                            <p style="margin-top: var(--space-2);">
                                Completa tus datos para finalizar el alta.
                            </p>

                            <form method="POST" action="{{ route('invitation.accept.store', $invitation->token) }}"
                                style="margin-top: var(--space-3);">
                                @csrf

                                <div class="form-group">
                                    <label class="form-label" for="name">Nombre</label>
                                    <input id="name" class="form-control" name="name" type="text"
                                        value="{{ old('name', $prefillUser->name ?? $invitation->signupRequest->requested_name ?? '') }}"
                                        required>
                                </div>

                                @if (!$emailExists)
                                    <div class="form-group">
                                        <label class="form-label" for="password">Password</label>
                                        <input id="password" class="form-control" name="password" type="password" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="password_confirmation">Confirmar password</label>
                                        <input id="password_confirmation" class="form-control" name="password_confirmation"
                                            type="password" required>
                                    </div>
                                @else
                                    <p style="margin-top: var(--space-2);">
                                        Se usarán tus datos de cuenta existentes para continuar con esta invitación.
                                    </p>
                                @endif

                                <button type="submit" class="btn btn-primary">
                                    {{ $invitation->type === 'owner_signup' ? 'Crear empresa y continuar' : 'Finalizar alta' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </x-card>
            </div>
        </div>
    </x-page>
@endsection