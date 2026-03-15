{{-- FILE: resources/views/assets/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Activos', 'url' => route('assets.index')],
            ['label' => $asset->name],
        ]" />

        <x-page-header title="Detalle del activo">
            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary">
                Editar
            </a>

            <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                onsubmit="return confirm('¿Eliminar activo?');" class="inline-form">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ AssetCatalog::label($asset->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $asset->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Estado</div>
                    <div class="summary-inline-value">
                        <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                            {{ AssetCatalog::statusLabel($asset->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">Relación</span>
                    <div class="detail-block-value">{{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Contacto relacionado</span>
                    <div class="detail-block-value">{{ $asset->party?->name ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Código interno</span>
                    <div class="detail-block-value">{{ $asset->internal_code ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Creado</span>
                    <div class="detail-block-value">{{ $asset->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Actualizado</span>
                    <div class="detail-block-value">{{ $asset->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Notas</span>
                    <div class="detail-block-value">{{ $asset->notes ?? '—' }}</div>
                </div>
            </div>
        </x-card>

    </x-page>
@endsection