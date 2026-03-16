{{-- FILE: resources/views/invitations/show.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Invitación')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel public-panel--lg">
                <x-page-header title="Invitación de acceso" vertical="vertical">
                    <p class="public-text">
                        Este enlace te permite completar el alta inicial y continuar con la configuración de tu acceso.
                    </p>
                </x-page-header>

                <x-card>
                    @if ($state === 'accepted')
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Invitación ya utilizada</h2>
                            <p class="dashboard-section-text">
                                Esta invitación ya fue aceptada anteriormente.
                            </p>
                        </div>

                        <p class="public-text">
                            Si ya completaste el proceso, puedes ingresar con tu cuenta.
                        </p>

                        <div class="form-actions">
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                Ingresar
                            </a>
                        </div>
                    @elseif ($state === 'expired')
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Invitación vencida</h2>
                            <p class="dashboard-section-text">
                                Este enlace ya no se encuentra disponible.
                            </p>
                        </div>

                        <p class="public-text">
                            Solicita un nuevo enlace a la persona o equipo que te lo envió.
                        </p>
                    @else
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Datos de la invitación</h2>
                            <p class="dashboard-section-text">
                                Revisa la información antes de continuar.
                            </p>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-block">
                                <span class="detail-block-label">Email invitado</span>
                                <div class="detail-block-value">{{ $invitation->email }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Tipo de invitación</span>
                                <div class="detail-block-value">{{ $invitation->type }}</div>
                            </div>

                            @if ($invitation->type === 'owner_signup' && $invitation->signupRequest)
                                <div class="detail-block detail-block--full">
                                    <span class="detail-block-label">Empresa solicitada</span>
                                    <div class="detail-block-value">{{ $invitation->signupRequest->company_name }}</div>
                                </div>
                            @endif

                            @if ($invitation->expires_at)
                                <div class="detail-block detail-block--full">
                                    <span class="detail-block-label">Vencimiento del enlace</span>
                                    <div class="detail-block-value">
                                        {{ $invitation->expires_at->format('d/m/Y H:i') }}
                                        <span class="helper-inline">· {{ $invitation->expires_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="hr-muted">

                        @if ($mustLogin)
                            <div class="dashboard-section-header">
                                <h2 class="dashboard-section-title">Cuenta existente detectada</h2>
                                <p class="dashboard-section-text">
                                    Ya existe una cuenta registrada con este email.
                                </p>
                            </div>

                            <p class="public-text">
                                Para continuar con esta invitación, inicia sesión con el mismo correo al que fue enviado
                                este
                                enlace.
                            </p>

                            <p class="public-text">
                                Una vez dentro, podrás aceptar la invitación y continuar con el proceso.
                            </p>

                            <div class="form-actions">
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    Iniciar sesión
                                </a>
                            </div>
                        @else
                            <div class="dashboard-section-header">
                                <h2 class="dashboard-section-title">Completa tu acceso</h2>
                                <p class="dashboard-section-text">
                                    Ingresa tus datos para finalizar el alta.
                                </p>
                            </div>

                            @if (!$emailExists)
                                <p class="public-text">
                                    Como todavía no tienes una cuenta registrada con este email, ahora podrás definir tu
                                    contraseña
                                    inicial.
                                </p>
                            @else
                                <p class="public-text">
                                    Se utilizará tu cuenta existente para continuar con esta invitación.
                                </p>
                            @endif

                            <p class="public-text">
                                Verifica que la información sea correcta antes de continuar.
                            </p>

                            <form method="POST" action="{{ route('invitation.accept.store', $invitation->token) }}"
                                class="form public-actions">
                                @csrf

                                <div class="form-group">
                                    <label class="form-label" for="name">Nombre</label>
                                    <input id="name" class="form-control @error('name') is-invalid @enderror"
                                        name="name" type="text"
                                        value="{{ old('name', $prefillUser->name ?? ($invitation->signupRequest->requested_name ?? '')) }}"
                                        placeholder="Tu nombre" required>
                                    <div class="form-help">
                                        Este nombre se utilizará para identificar tu cuenta dentro del sistema.
                                    </div>
                                    @error('name')
                                        <div class="form-help is-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if (!$emailExists)
                                    <div class="form-group">
                                        <label class="form-label" for="password">Contraseña</label>
                                        <input id="password" class="form-control @error('password') is-invalid @enderror"
                                            name="password" type="password" required>
                                        <div class="form-help">
                                            Elige una contraseña para activar tu acceso inicial.
                                        </div>
                                        @error('password')
                                            <div class="form-help is-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                                        <input id="password_confirmation" class="form-control" name="password_confirmation"
                                            type="password" required>
                                        <div class="form-help">
                                            Vuelve a escribir la contraseña para confirmar.
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-success">
                                        Se usarán tus datos de cuenta existentes para continuar con esta invitación.
                                    </div>
                                @endif

                                <div class="alert alert-success">
                                    Este enlace está asociado al email <strong>{{ $invitation->email }}</strong>.
                                    Si continúas, completarás el proceso de acceso correspondiente a esta invitación.
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        {{ $invitation->type === 'owner_signup' ? 'Continuar con el alta de empresa' : 'Aceptar invitación y continuar' }}
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
