{{-- FILE: resources/views/parties/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Catalogs\AssetCatalog;
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
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ PartyCatalog::label($party->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $party->name }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">Nombre visible</span>
                    <div class="detail-block-value">{{ $party->display_name ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Tipo documento</span>
                    <div class="detail-block-value">{{ $party->document_type ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Número documento</span>
                    <div class="detail-block-value">{{ $party->document_number ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">CUIT / Tax ID</span>
                    <div class="detail-block-value">{{ $party->tax_id ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Email</span>
                    <div class="detail-block-value">{{ $party->email ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Teléfono</span>
                    <div class="detail-block-value">{{ $party->phone ?: '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Dirección</span>
                    <div class="detail-block-value">{{ $party->address ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Activo</span>
                    <div class="detail-block-value">{{ $party->is_active ? 'Sí' : 'No' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Creado</span>
                    <div class="detail-block-value">{{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Actualizado</span>
                    <div class="detail-block-value">{{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Notas</span>
                    <div class="detail-block-value">{{ $party->notes ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Activos vinculados</h2>
                <p class="dashboard-section-text">
                    Activos actualmente asociados a este contacto.
                </p>
            </div>

            @if ($assets->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Relación</th>
                                <th>Código</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assets as $asset)
                                <tr>
                                    <td>{{ $asset->id }}</td>
                                    <td>
                                        <a href="{{ route('assets.show', $asset) }}">
                                            {{ $asset->name }}
                                        </a>
                                    </td>
                                    <td>{{ AssetCatalog::label($asset->kind) }}</td>
                                    <td>{{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}</td>
                                    <td>{{ $asset->internal_code ?? '—' }}</td>
                                    <td>
                                        <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                                            {{ AssetCatalog::statusLabel($asset->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">Este contacto no tiene activos vinculados.</p>
            @endif
        </x-card>

    </x-page>
@endsection
