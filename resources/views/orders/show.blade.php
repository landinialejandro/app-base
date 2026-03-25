{{-- FILE: resources/views/orders/show.blade.php | V7 --}}

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

        $quoteCount = $documents->where('kind', DocumentCatalog::KIND_QUOTE)->count();
        $deliveryNoteCount = $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->count();
        $invoiceCount = $documents->where('kind', DocumentCatalog::KIND_INVOICE)->count();
        $workOrderCount = $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->count();

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

                <x-slot:actions>
                    @can('update', $order)
                        <a href="{{ route('orders.items.create', ['order' => $order] + $trailQuery) }}"
                            class="btn btn-success">
                            <x-icons.plus />
                            <span>Agregar ítem</span>
                        </a>
                    @endcan
                </x-slot:actions>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">
                    <x-card class="list-card">
                        @include('orders.items.partials.table', [
                            'order' => $order,
                            'items' => $items,
                            'emptyMessage' => 'No hay ítems cargados en esta orden.',
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
                                <div class="summary-inline-label">Total orden</div>
                                <div class="summary-inline-value">${{ number_format($order->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="documents" hidden>
                <div class="tab-panel-stack">
                    <x-tab-toolbar label="Acciones de documentos de la orden">
                        <x-slot:tabs>
                            <span class="tab-toolbar-title">Documentos de la orden</span>
                        </x-slot:tabs>

                        <x-slot:actions>
                            <form method="POST"
                                action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                                class="inline-form"
                                @if ($quoteCount > 0) data-action="app-confirm-submit"
                                data-confirm-message="Esta orden ya tiene {{ $quoteCount }} presupuesto(s) asociado(s). ¿Deseas crear otro?" @endif>
                                @csrf
                                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_QUOTE }}">
                                <button type="submit" class="btn btn-secondary">
                                    {{ $quoteCount > 0 ? 'Crear otro presupuesto' : 'Crear presupuesto' }}
                                </button>
                            </form>

                            <form method="POST"
                                action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                                class="inline-form"
                                @if ($deliveryNoteCount > 0) data-action="app-confirm-submit"
                                data-confirm-message="Esta orden ya tiene {{ $deliveryNoteCount }} remito(s) asociado(s). ¿Deseas crear otro?" @endif>
                                @csrf
                                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_DELIVERY_NOTE }}">
                                <button type="submit" class="btn btn-secondary">
                                    {{ $deliveryNoteCount > 0 ? 'Crear otro remito' : 'Crear remito' }}
                                </button>
                            </form>

                            <form method="POST"
                                action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                                class="inline-form"
                                @if ($invoiceCount > 0) data-action="app-confirm-submit"
                                data-confirm-message="Esta orden ya tiene {{ $invoiceCount }} factura(s) asociada(s). ¿Deseas crear otra?" @endif>
                                @csrf
                                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_INVOICE }}">
                                <button type="submit" class="btn btn-secondary">
                                    {{ $invoiceCount > 0 ? 'Crear otra factura' : 'Crear factura' }}
                                </button>
                            </form>

                            <form method="POST"
                                action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                                class="inline-form"
                                @if ($workOrderCount > 0) data-action="app-confirm-submit"
                                data-confirm-message="Esta orden ya tiene {{ $workOrderCount }} orden(es) de trabajo asociada(s). ¿Deseas crear otra?" @endif>
                                @csrf
                                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_WORK_ORDER }}">
                                <button type="submit" class="btn btn-secondary">
                                    {{ $workOrderCount > 0 ? 'Crear otra orden de trabajo' : 'Crear orden de trabajo' }}
                                </button>
                            </form>
                        </x-slot:actions>
                    </x-tab-toolbar>

                    @include('documents.partials.embedded-tabs', [
                        'documents' => $documents,
                        'showParty' => false,
                        'showAsset' => false,
                        'showOrder' => false,
                        'emptyMessage' => 'No hay documentos asociados para mostrar.',
                        'allLabel' => 'Todos',
                        'tabsId' => 'order-documents-tabs',
                        'trailQuery' => $trailQuery,
                    ])
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.panel', [
                        'attachable' => $order,
                        'attachments' => $attachments,
                        'title' => 'Adjuntos de la orden',
                        'emptyMessage' => 'Esta orden no tiene adjuntos cargados.',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
