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
            <p class="mb-0">No tenés empresas asignadas.</p>
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