{{-- FILE: resources/views/documents/show.blade.php | V13 --}}

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
            @can('update', $document)
                <a href="{{ route('documents.edit', ['document' => $document] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $document)
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
            @endcan

            <a href="{{ route('documents.print', ['document' => $document]) }}" class="btn btn-secondary" target="_blank">
                Imprimir
            </a>

            <a href="{{ route('documents.pdf', ['document' => $document]) }}" class="btn btn-secondary">
                Descargar PDF
            </a>

            <a href="{{ $backUrl }}" class="btn btn-secondary btn-icon" title="{{ $backLabel }}"
                aria-label="{{ $backLabel }}">
                <x-icons.chevron-left />
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

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $document->notes ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Trazabilidad" full>
                    Creado: {{ $document->created_at?->format('d/m/Y H:i') ?? '—' }}<br>
                    Actualizado: {{ $document->updated_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>

            <x-tab-toolbar label="Secciones del documento">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones del documento">

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

                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">
                    @include('documents.items.partials.embedded', [
                        'document' => $document,
                        'items' => $items,
                        'trailQuery' => $trailQuery,
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachableType' => 'document',
                        'attachableId' => $document->id,
                        'trailQuery' => $trailQuery,
                        'tabsId' => 'document-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>

        </div>
    </x-page>
@endsection
