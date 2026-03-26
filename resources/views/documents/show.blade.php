{{-- FILE: resources/views/documents/show.blade.php | V11 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\DocumentCatalog;
    use App\Support\Navigation\NavigationTrail;

    $items = $document->items->sortBy('position')->values();
    $attachments = $document->attachments->values();

    $documentDetailTitle = match ($document->kind) {
        DocumentCatalog::KIND_QUOTE => 'Detalle del presupuesto',
        DocumentCatalog::KIND_INVOICE => 'Detalle de la factura',
        DocumentCatalog::KIND_DELIVERY_NOTE => 'Detalle del remito',
        DocumentCatalog::KIND_WORK_ORDER => 'Detalle de la orden de trabajo',
        DocumentCatalog::KIND_RECEIPT => 'Detalle del recibo',
        DocumentCatalog::KIND_CREDIT_NOTE => 'Detalle de la nota de crédito',
        default => 'Detalle del documento',
    };

    $canUpdateDocument = auth()->user()->can('update', $document);
    $canDeleteDocument = auth()->user()->can('delete', $document);

    $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
    $trailQuery = NavigationTrail::toQuery($navigationTrail);
    $backUrl = NavigationTrail::previousUrl($navigationTrail, route('documents.index'));
    $previousNode = NavigationTrail::previous($navigationTrail);

    $backLabel = ($previousNode['key'] ?? null) === 'orders.show' ? 'Volver a la orden' : 'Volver';
@endphp

@section('title', $documentDetailTitle)

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$documentDetailTitle">
            @if ($canUpdateDocument)
                <a href="{{ route('documents.edit', ['document' => $document] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endif

            @if ($canDeleteDocument)
                <form method="POST" action="{{ route('documents.destroy', ['document' => $document] + $trailQuery) }}"
                    class="inline-form" data-action="app-confirm-submit"
                    data-confirm-message="{{ $items->count()
                        ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                        : '¿Deseas eliminar este documento?' }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endif

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                {{ $backLabel }}
            </a>
        </x-page-header>

        <x-show-summary details-id="document-more-detail">
            <x-show-summary-item label="Contacto">
                @if ($document->party)
                    <a href="{{ route('parties.show', ['party' => $document->party] + $trailQuery) }}">
                        {{ $document->party->name }}
                    </a>
                @else
                    —
                @endif
            </x-show-summary-item>

            <x-show-summary-item label="Fecha">
                {{ $document->issued_at?->format('d/m/Y') ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Número">
                {{ $document->number ?: 'Sin número' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ DocumentCatalog::label($document->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Estado">
                    <span class="status-badge {{ DocumentCatalog::badgeClass($document->status) }}">
                        {{ DocumentCatalog::statusLabel($document->status) }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Orden asociada">
                    @if ($document->order)
                        <a href="{{ route('orders.show', ['order' => $document->order] + $trailQuery) }}">
                            {{ $document->order->number ?: 'Orden #' . $document->order->id }}
                        </a>
                    @else
                        —
                    @endif
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    @if ($document->asset)
                        <a href="{{ route('assets.show', ['asset' => $document->asset] + $trailQuery) }}">
                            {{ $document->asset->name }}
                        </a>
                    @else
                        —
                    @endif
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Fecha de vencimiento">
                    {{ $document->due_at?->format('d/m/Y') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Moneda">
                    {{ $document->currency_code ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones secundarias del documento">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones secundarias del documento">
                        <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                            aria-selected="true">
                            Ítems
                            @if ($items->count())
                                ({{ $items->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="attachments" role="tab"
                            aria-selected="false">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="amounts" role="tab"
                            aria-selected="false">
                            Importes
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="trace" role="tab" aria-selected="false">
                            Notas y trazabilidad
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>

                <x-slot:actions>
                    @if ($canUpdateDocument)
                        <a href="{{ route('documents.items.create', ['document' => $document] + $trailQuery) }}"
                            class="btn btn-success">
                            <x-icons.plus />
                            <span>Agregar ítem</span>
                        </a>

                        <a href="{{ route(
                            'attachments.create',
                            [
                                'attachable_type' => 'document',
                                'attachable_id' => $document->id,
                                'return_to' => url()->current(),
                            ] + $trailQuery,
                        ) }}"
                            class="btn btn-success">
                            <x-icons.plus />
                            <span>Agregar adjunto</span>
                        </a>
                    @endif
                </x-slot:actions>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">
                    <x-card class="list-card">
                        @include('documents.items.partials.table', [
                            'document' => $document,
                            'items' => $items,
                            'emptyMessage' => 'No hay ítems cargados en este documento.',
                            'trailQuery' => $trailQuery,
                        ])
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

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachableType' => 'document',
                        'attachableId' => $document->id,
                        'trailQuery' => $trailQuery,
                        'returnTo' => url()->current(),
                        'tabsId' => 'document-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="amounts" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <x-show-summary-item-detail-block label="Subtotal">
                                ${{ number_format($document->subtotal, 2, ',', '.') }}
                            </x-show-summary-item-detail-block>

                            <x-show-summary-item-detail-block label="Impuestos">
                                ${{ number_format($document->tax_total, 2, ',', '.') }}
                            </x-show-summary-item-detail-block>

                            <x-show-summary-item-detail-block label="Total">
                                ${{ number_format($document->total, 2, ',', '.') }}
                            </x-show-summary-item-detail-block>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="trace" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <x-show-summary-item-detail-block label="Notas" full>
                                {{ $document->notes ?: '—' }}
                            </x-show-summary-item-detail-block>

                            <x-show-summary-item-detail-block label="Creado por">
                                {{ $document->creator?->name ?: '—' }}
                            </x-show-summary-item-detail-block>

                            <x-show-summary-item-detail-block label="Actualizado por">
                                {{ $document->updater?->name ?: '—' }}
                            </x-show-summary-item-detail-block>

                            <x-show-summary-item-detail-block label="Actualizado el">
                                {{ $document->updated_at?->format('d/m/Y H:i') ?: '—' }}
                            </x-show-summary-item-detail-block>
                        </div>
                    </x-card>
                </div>
            </section>
        </div>
    </x-page>
@endsection
