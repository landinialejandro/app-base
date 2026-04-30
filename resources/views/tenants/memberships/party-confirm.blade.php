{{-- FILE: resources/views/tenants/memberships/party-confirm.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Ficha ampliada')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Perfil de empresa', 'url' => route('tenant.profile.show', ['tab' => 'users'])],
            ['label' => 'Ficha ampliada'],
        ]" />

        <x-page-header title="Ficha ampliada" />

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">
                    Crear o vincular ficha ampliada
                </h2>

                <p class="dashboard-section-text">
                    Esta acción permite asociar datos ampliados a la pertenencia de este usuario dentro de la empresa.
                    No todos los usuarios internos necesitan una ficha ampliada.
                </p>
            </div>

            <div class="detail-grid">
                <div class="detail-block">
                    <span class="detail-block-label">Usuario</span>
                    <div class="detail-block-value">{{ $membership->user?->name ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Email</span>
                    <div class="detail-block-value">{{ $membership->user?->email ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Tipo sugerido</span>
                    <div class="detail-block-value">Colaborador</div>
                </div>
            </div>

            <hr class="hr-muted">

            @if ($matchingParty)
                <p class="form-help">
                    Ya existe un contacto con el mismo email dentro de esta empresa. Podés vincular esa ficha existente.
                </p>

                <form method="POST" action="{{ route('tenant.memberships.party.store', $membership) }}" class="form">
                    @csrf
                    <input type="hidden" name="mode" value="link_existing">

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Vincular ficha existente
                        </button>

                        <a href="{{ route('parties.show', $matchingParty) }}" class="btn btn-secondary">
                            Ver contacto
                        </a>

                        <a href="{{ route('tenant.profile.show', ['tab' => 'users']) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            @else
                <p class="form-help">
                    No hay una ficha vinculada ni un contacto existente con este email. Si necesitás registrar más datos,
                    podés crear una ficha nueva de colaborador.
                </p>

                <form method="POST" action="{{ route('tenant.memberships.party.store', $membership) }}" class="form">
                    @csrf
                    <input type="hidden" name="mode" value="create_new">

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            Crear ficha ampliada
                        </button>

                        <a href="{{ route('tenant.profile.show', ['tab' => 'users']) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            @endif

            <x-dev-component-version name="tenants.memberships.party-confirm" version="V1" />
        </x-card>
    </x-page>
@endsection