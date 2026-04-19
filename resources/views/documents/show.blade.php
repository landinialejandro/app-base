{{-- FILE: resources/views/documents/show.blade.php | V16 --}}

@extends('layouts.app')

@php
    use App\Support\Assets\AssetLinkedAction;
    use App\Support\Catalogs\DocumentCatalog;
    use App\Support\Navigation\NavigationTrail;
    use App\Support\Parties\PartyLinkedAction;

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

    $partyAction = PartyLinkedAction::forParty($document->party, $trailQuery, 'Contacto');
    $assetAction = AssetLinkedAction::forAsset($document->asset, $trailQuery, 'Activo');
@endphp

@section('title', $documentDetailTitle)

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$documentDetailTitle">
            @can('update', $document)
                <x-button-edit :href="route('documents.edit', ['document' => $document] + $trailQuery)" />
            @endcan

            @can('delete', $document)
                <x-button-delete :action="route('documents.destroy', ['document' => $document] + $trailQuery)" :message="$items->count()
                    ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                    : '¿Deseas eliminar este documento?'" />
            @endcan

            <x-button-secondary :href="route('documents.print', ['document' => $document])" target="_blank">
                Imprimir
            </x-button-secondary>

            <x-button-secondary :href="route('documents.pdf', ['document' => $document])">
                Descargar PDF
            </x-button-secondary>

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="document-more-detail">
            <x-show-summary-item label="Contacto">
                @include('parties.components.linked-party-action', [
                    'action' => $partyAction,
                    'variant' => 'summary',
                ])
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
                    @include('assets.components.linked-asset-action', [
                        'action' => $assetAction,
                        'variant' => 'summary',
                    ])
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
                        'attachable' => $document,
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
