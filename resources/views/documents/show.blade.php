{{-- FILE: resources/views/documents/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle del documento')

@section('content')
    @php
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Catalogs\ProductCatalog;

        $items = $document->items->sortBy('position');
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos', 'url' => route('documents.index')],
            ['label' => $document->number ?: 'Sin número'],
        ]" />

        <x-page-header title="Detalle del documento">
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline-form"
                data-action="app-confirm-submit"
                data-confirm-message="{{ $document->items->count()
                    ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                    : '¿Deseas eliminar este documento?' }}">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ DocumentCatalog::label($document->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Número</div>
                    <div class="summary-inline-value">{{ $document->number ?: 'Sin número' }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">Estado</span>
                    <div class="detail-block-value">
                        <span class="status-badge {{ DocumentCatalog::badgeClass($document->status) }}">
                            {{ DocumentCatalog::label($document->status) }}
                        </span>
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Contacto</span>
                    <div class="detail-block-value">{{ $document->party?->name ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Orden asociada</span>
                    <div class="detail-block-value">
                        @if ($document->order)
                            <a href="{{ route('orders.show', $document->order) }}">
                                {{ $document->order->number ?: 'Sin número' }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Activo</span>
                    <div class="detail-block-value">
                        @if ($document->asset)
                            <a href="{{ route('assets.show', $document->asset) }}">
                                {{ $document->asset->name }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Fecha de emisión</span>
                    <div class="detail-block-value">{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Fecha de vencimiento</span>
                    <div class="detail-block-value">{{ $document->due_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Moneda</span>
                    <div class="detail-block-value">{{ $document->currency_code ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones secundarias del documento">
                <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                    aria-selected="true">
                    Ítems
                    @if ($items->count())
                        ({{ $items->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="amounts" role="tab" aria-selected="false">
                    Importes
                </button>

                <button type="button" class="tabs-link" data-tab-link="trace" role="tab" aria-selected="false">
                    Notas y trazabilidad
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">

                    <x-page-header title="Ítems del documento">
                        <a href="{{ route('documents.items.create', $document) }}" class="btn btn-primary">
                            Agregar ítem
                        </a>
                    </x-page-header>

                    <x-card class="list-card">
                        @if ($items->count())
                            <div class="table-wrap list-scroll">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Posición</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Precio unitario</th>
                                            <th>Total línea</th>
                                            <th class="compact-actions-cell">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->position }}</td>
                                                <td>{{ ProductCatalog::label($item->kind) }}</td>
                                                <td>{{ $item->description }}</td>
                                                <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->line_total, 2, ',', '.') }}</td>
                                                <td class="compact-actions-cell">
                                                    <div class="compact-actions">
                                                        <a href="{{ route('documents.items.edit', [$document, $item]) }}"
                                                            class="btn btn-secondary btn-icon" title="Editar ítem"
                                                            aria-label="Editar ítem">
                                                            <x-icons.pencil />
                                                        </a>

                                                        <form method="POST"
                                                            action="{{ route('documents.items.destroy', [$document, $item]) }}"
                                                            class="inline-form" data-action="app-confirm-submit"
                                                            data-confirm-message="¿Deseas eliminar este ítem?">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="btn btn-danger btn-icon"
                                                                title="Eliminar ítem" aria-label="Eliminar ítem">
                                                                <x-icons.trash />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">No hay ítems cargados en este documento.</p>
                        @endif
                    </x-card>

                    <x-card>
                        <div class="summary-inline-grid">
                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Cantidad de ítems</div>
                                <div class="summary-inline-value">{{ $items->count() }}</div>
                            </div>

                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Total documento</div>
                                <div class="summary-inline-value">${{ number_format($document->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>

                </div>
            </section>

            <section class="tab-panel" data-tab-panel="amounts" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <div class="detail-block">
                                <span class="detail-block-label">Subtotal</span>
                                <div class="detail-block-value">${{ number_format($document->subtotal, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Impuestos</span>
                                <div class="detail-block-value">${{ number_format($document->tax_total, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Total</span>
                                <div class="detail-block-value">${{ number_format($document->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="trace" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <div class="detail-block">
                                <span class="detail-block-label">Creado por</span>
                                <div class="detail-block-value">{{ $document->creator?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Actualizado por</span>
                                <div class="detail-block-value">{{ $document->updater?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block detail-block--full">
                                <span class="detail-block-label">Notas</span>
                                <div class="detail-block-value">{{ $document->notes ?: '—' }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>
        </div>

    </x-page>
@endsection
