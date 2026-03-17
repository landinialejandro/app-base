{{-- FILE: resources/views/parties/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')

    @php
        $documents = $documents ?? collect();
        $assets = $assets ?? collect();
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos', 'url' => route('parties.index')],
            ['label' => $party->name],
        ]" />

        <x-page-header title="Detalle del contacto">
            <a href="{{ route('parties.edit', $party) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('parties.destroy', $party) }}" class="inline-form"
                data-action="app-confirm-submit" data-confirm-message="¿Eliminar contacto?">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('parties.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $party->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Teléfono</div>
                    <div class="summary-inline-value">{{ $party->phone ?: '—' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Email</div>
                    <div class="summary-inline-value">{{ $party->email ?: '—' }}</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#party-detail-panel" data-toggle-text-expanded="Ocultar detalle"
                    data-toggle-text-collapsed="Más detalle">
                    Más detalle
                </button>
            </div>

            <div id="party-detail-panel" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ \App\Support\Catalogs\PartyCatalog::label($party->kind) }}</div>
                    </div>

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
                        <span class="detail-block-label">Activo</span>
                        <div class="detail-block-value">{{ $party->is_active ? 'Sí' : 'No' }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Dirección</span>
                        <div class="detail-block-value">{{ $party->address ?: '—' }}</div>
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
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones del contacto">
                <button type="button" class="tabs-link is-active" data-tab-link="documents" role="tab"
                    aria-selected="true">
                    Documentos
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="assets" role="tab" aria-selected="false">
                    Activos
                    @if ($assets->count())
                        ({{ $assets->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="documents">
                <div class="tab-panel-stack">
                    @include('documents.partials.embedded-tabs', [
                        'documents' => $documents,
                        'showParty' => false,
                        'showAsset' => true,
                        'showOrder' => true,
                        'emptyMessage' => 'Este contacto no tiene documentos vinculados.',
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="assets" hidden>
                <div class="tab-panel-stack">
                    @include('assets.partials.embedded-tabs', [
                        'assets' => $assets,
                        'showParty' => false,
                        'emptyMessage' => 'Este contacto no tiene activos vinculados.',
                    ])
                </div>
            </section>
        </div>

    </x-page>
@endsection
