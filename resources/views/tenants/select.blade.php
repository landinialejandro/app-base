{{-- FILE: resources/views/tenants/select.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Seleccionar empresa')

@section('content')
  <x-page>
    <div class="welcome-page">
      <div style="width: 520px; max-width: 100%;">

        <x-page-header title="Seleccionar empresa" vertical="vertical" />

        <x-card>
          @if ($tenants->isEmpty())
            <p>No tenés empresas asignadas.</p>
            <p style="margin-top: var(--space-2);">
              Para ingresar al sistema necesitas una invitación válida asociada a una empresa.
              Si crees que esto es un error, contacta al administrador correspondiente.
            </p>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: var(--space-3);">
              @csrf
              <button type="submit" class="btn btn-secondary">
                Cerrar sesión
              </button>
            </form>
          @else
            <div class="tenant-list">
              @foreach ($tenants as $tenant)
                <form method="POST" action="{{ route('tenants.select.store', $tenant) }}">
                  @csrf

                  <button type="submit" class="tenant-option">
                    <span class="tenant-option-name">{{ $tenant->name }}</span>
                    <span class="tenant-option-meta">{{ $tenant->slug }}</span>
                  </button>
                </form>
              @endforeach
            </div>
          @endif
        </x-card>

      </div>
    </div>
  </x-page>
@endsection