{{-- FILE: resources/views/orders/show.blade.php | V9 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;

        $items = $order->items->sortBy('position')->values();
        $documents = $order->documents->sortByDesc('id')->values();
        $attachments = $order->attachments ?? collect();

        $orderDetailTitle = match ($order->kind) {
            OrderCatalog::KIND_SALE => 'Detalle de la orden de venta',
            OrderCatalog::KIND_PURCHASE => 'Detalle de la orden de compra',
            OrderCatalog::KIND_SERVICE => 'Detalle de la orden de servicio',
            default => 'Detalle de la orden',
        };

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));
        $previousNode = NavigationTrail::previous($navigationTrail);
        $backLabel = ($previousNode['key'] ?? null) === 'appointments.show' ? 'Volver al turno' : 'Volver';
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$orderDetailTitle">
            @can('update', $order)
                <a href="{{ route('orders.edit', ['order' => $order] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $order)
                <form method="POST" action="{{ route('orders.destroy', ['order' => $order] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit"
                    data-confirm-message="{{ $items->count()
                        ? 'Esta orden tiene ítems cargados. Si la eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                        : '¿Deseas eliminar esta orden?' }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                {{ $backLabel }}
            </a>
        </x-page-header>

        <x-show-summary details-id="order-more-detail">
            <x-show-summary-item label="Contacto">
                {{ $order->party?->name ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Fecha">
                {{ $order->ordered_at?->format('d/m/Y') ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Número">
                {{ $order->number ?: 'Sin número' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ OrderCatalog::label($order->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Tarea origen">
                    @if ($order->task)
                        <a href="{{ route('tasks.show', ['task' => $order->task] + $trailQuery) }}">
                            {{ $order->task->name }}
                        </a>
                    @else
                        —
                    @endif
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Estado">
                    <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                        {{ OrderCatalog::statusLabel($order->status) }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    @if ($order->asset)
                        <a href="{{ route('assets.show', ['asset' => $order->asset] + $trailQuery) }}">
                            {{ $order->asset->name }}
                        </a>
                    @else
                        —
                    @endif
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $order->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones secundarias de la orden">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones secundarias de la orden">
                        <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                            aria-selected="true">
                            Ítems
                            @if ($items->count())
                                ({{ $items->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="documents" role="tab"
                            aria-selected="false">
                            Documentos
                            @if ($documents->count())
                                ({{ $documents->count() }})
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
                    @include('orders.items.partials.embedded', [
                        'order' => $order,
                        'items' => $items,
                        'emptyMessage' => 'No hay ítems cargados en esta orden.',
                        'trailQuery' => $trailQuery,
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="documents" hidden>
                <div class="tab-panel-stack">
                    @include('documents.partials.embedded-tabs', [
                        'order' => $order,
                        'documents' => $documents,
                        'showParty' => false,
                        'showAsset' => false,
                        'showOrder' => false,
                        'emptyMessage' => 'No hay documentos asociados para mostrar.',
                        'allLabel' => 'Todos',
                        'tabsId' => 'order-documents-tabs',
                        'trailQuery' => $trailQuery,
                        'quoteCount' => $documents->where('kind', DocumentCatalog::KIND_QUOTE)->count(),
                        'deliveryNoteCount' => $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->count(),
                        'invoiceCount' => $documents->where('kind', DocumentCatalog::KIND_INVOICE)->count(),
                        'workOrderCount' => $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->count(),
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachableType' => 'order',
                        'attachableId' => $order->id,
                        'trailQuery' => $trailQuery,
                        'returnTo' => url()->current(),
                        'tabsId' => 'order-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
