{{-- FILE: resources/views/parties/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos', 'url' => route('parties.index')],
            ['label' => $party->name],
        ]" />

        <x-page-header title="Detalle del contacto">
            <a href="{{ route('parties.edit', $party) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('parties.destroy', $party) }}"
                onsubmit="return confirm('¿Eliminar contacto?');" class="inline-form">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('parties.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="detail-list">
                <div class="detail-label">ID</div>
                <div class="detail-value">{{ $party->id }}</div>

                <div class="detail-label">Tipo</div>
                <div class="detail-value">{{ PartyCatalog::label($party->kind) }}</div>

                <div class="detail-label">Nombre</div>
                <div class="detail-value">{{ $party->name }}</div>

                <div class="detail-label">Nombre visible</div>
                <div class="detail-value">{{ $party->display_name ?: '—' }}</div>

                <div class="detail-label">Tipo documento</div>
                <div class="detail-value">{{ $party->document_type ?: '—' }}</div>

                <div class="detail-label">Número documento</div>
                <div class="detail-value">{{ $party->document_number ?: '—' }}</div>

                <div class="detail-label">CUIT / Tax ID</div>
                <div class="detail-value">{{ $party->tax_id ?: '—' }}</div>

                <div class="detail-label">Email</div>
                <div class="detail-value">{{ $party->email ?: '—' }}</div>

                <div class="detail-label">Teléfono</div>
                <div class="detail-value">{{ $party->phone ?: '—' }}</div>

                <div class="detail-label">Dirección</div>
                <div class="detail-value">{{ $party->address ?: '—' }}</div>

                <div class="detail-label">Notas</div>
                <div class="detail-value">{{ $party->notes ?: '—' }}</div>

                <div class="detail-label">Activo</div>
                <div class="detail-value">{{ $party->is_active ? 'Sí' : 'No' }}</div>

                <div class="detail-label">Creado</div>
                <div class="detail-value">{{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}</div>

                <div class="detail-label">Actualizado</div>
                <div class="detail-value">{{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
            </div>
        </x-card>

    </x-page>
@endsection