{{-- FILE: resources/views/tenants/select.blade.php --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Seleccionar empresa')

@section('content')
  <x-page>
    <div class="welcome-page">
      <div class="public-panel public-panel--md">
        <x-page-header title="Seleccionar empresa" vertical="vertical" />

        <x-card>
          @if ($tenants->isEmpty())
            <p class="public-text">No tenés empresas asignadas.</p>
            <p class="public-text">
              Para ingresar al sistema necesitas una invitación válida asociada a una empresa.
              Si crees que esto es un error, contacta al administrador correspondiente.
            </p>

            <div class="public-actions">
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">
                  Cerrar sesión
                </button>
              </form>
            </div>
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