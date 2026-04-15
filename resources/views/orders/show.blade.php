{{-- FILE: resources/views/orders/show.blade.php | V21 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Appointments\AppointmentLinkedAction;
        use App\Support\Assets\AssetLinkedAction;
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Parties\PartyLinkedAction;

        $attachments = $order->attachments ?? collect();
        $documents = $order->documents ?? collect();
        $items = $order->items ?? collect();
        $inventoryMovements = $order->inventoryMovements ?? collect();
        $inventoryProducts = $inventoryProducts ?? collect();

        $tenant = app('tenant');
        $user = auth()->user();

        $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);
        $supportsAssetsModule = $supportsAssetsModule ?? true;
        $supportsProductsModule = $supportsProductsModule ?? true;
        $supportsDocumentsModule = $supportsDocumentsModule ?? true;
        $supportsTasksModule = $supportsTasksModule ?? true;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));

        $canViewLinkedTask = $supportsTasksModule && $order->task && $user && $user->can('view', $order->task);

        $canViewLinkedDocuments = $supportsDocumentsModule;
        $canViewLinkedProducts = $supportsProductsModule;

        $partyAction = PartyLinkedAction::forParty($order->party, $trailQuery, 'Contacto');
        $assetAction = AssetLinkedAction::forAsset($order->asset, $trailQuery, 'Activo');
        $appointmentAction = AppointmentLinkedAction::forOrder($order, $trailQuery, true);
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle de la orden">
            @can('update', $order)
                <a href="{{ route('orders.edit', ['order' => $order] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $order)
                <form method="POST" action="{{ route('orders.destroy', ['order' => $order] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar orden?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <a href="{{ $backUrl }}" class="btn btn-secondary" title="Volver" aria-label="Volver">
                <x-icons.chevron-left />
            </a>
        </x-page-header>

        <x-show-summary details-id="order-more-detail">
            <x-show-summary-item label="Número">
                {{ $order->number ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Contacto">
                @include('parties.components.linked-party-action', [
                    'action' => $partyAction,
                    'variant' => 'summary',
                ])
            </x-show-summary-item>

            <x-show-summary-item label="Estado">
                <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                    {{ OrderCatalog::statusLabel($order->status) }}
                </span>
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ OrderCatalog::kindLabel($order->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Fecha">
                    {{ $order->ordered_at?->format('d/m/Y') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Turno">
                    @include('appointments.components.linked-appointment-action', [
                        'action' => $appointmentAction,
                        'variant' => 'summary',
                    ])
                </x-show-summary-item-detail-block>

                @if ($supportsAssetsModule)
                    <x-show-summary-item-detail-block label="Activo">
                        @include('assets.components.linked-asset-action', [
                            'action' => $assetAction,
                            'variant' => 'summary',
                        ])
                    </x-show-summary-item-detail-block>
                @endif

                @if ($supportsTasksModule)
                    <x-show-summary-item-detail-block label="Tarea">
                        @if ($canViewLinkedTask)
                            <a href="{{ route('tasks.show', ['task' => $order->task] + $trailQuery) }}">
                                {{ $order->task->name ?: 'Tarea #' . $order->task->id }}
                            </a>
                        @else
                            {{ $order->task?->name ?: ($order->task ? 'Tarea #' . $order->task->id : '—') }}
                        @endif
                    </x-show-summary-item-detail-block>
                @endif

                <x-show-summary-item-detail-block label="Creado">
                    {{ $order->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $order->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $order->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs">
            <x-tab-toolbar label="Secciones de la orden">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones de la orden">
                        <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                            aria-selected="true">
                            Ítems
                            @if ($items->count())
                                ({{ $items->count() }})
                            @endif
                        </button>

                        @if ($supportsProductsModule)
                            <button type="button" class="tabs-link" data-tab-link="inventory" role="tab"
                                aria-selected="false">
                                Movimientos de stock
                                @if ($inventoryMovements->count())
                                    ({{ $inventoryMovements->count() }})
                                @endif
                            </button>
                        @endif

                        @if ($supportsDocumentsModule)
                            <button type="button" class="tabs-link" data-tab-link="documents" role="tab"
                                aria-selected="false">
                                Documentos
                                @if ($documents->count())
                                    ({{ $documents->count() }})
                                @endif
                            </button>
                        @endif

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
                        'trailQuery' => $trailQuery,
                        'supportsProductsModule' => $supportsProductsModule,
                    ])
                </div>
            </section>

            @if ($supportsProductsModule)
                <section class="tab-panel" data-tab-panel="inventory" hidden>
                    <div class="tab-panel-stack">
                        @can('update', $order)
                            @if ($inventoryProducts->count())
                                <x-card>
                                    <div class="detail-grid">
                                        <div class="detail-block">
                                            <div class="detail-label">Registrar consumo</div>
                                            <div class="detail-value">Descuenta stock por uso interno en la orden.</div>
                                        </div>

                                        <div class="detail-block">
                                            <div class="detail-label">Registrar entrega</div>
                                            <div class="detail-value">Descuenta stock por entrega vinculada a la orden.</div>
                                        </div>
                                    </div>

                                    <div class="form-grid">
                                        <form
                                            action="{{ route('orders.inventory.consumir', ['order' => $order] + $trailQuery) }}"
                                            method="POST" class="form">
                                            @csrf

                                            <div class="form-group">
                                                <label for="inventory_consumir_product_id" class="form-label">Producto</label>
                                                <select id="inventory_consumir_product_id" name="product_id"
                                                    class="form-control" required>
                                                    <option value="">Seleccionar producto</option>
                                                    @foreach ($inventoryProducts as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_consumir_quantity" class="form-label">Cantidad</label>
                                                <input id="inventory_consumir_quantity" name="quantity" type="number"
                                                    step="0.01" min="0.01" class="form-control" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_consumir_note" class="form-label">Nota</label>
                                                <input id="inventory_consumir_note" name="note" type="text"
                                                    class="form-control" placeholder="Opcional">
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-secondary">Registrar consumo</button>
                                            </div>
                                        </form>

                                        <form
                                            action="{{ route('orders.inventory.entregar', ['order' => $order] + $trailQuery) }}"
                                            method="POST" class="form">
                                            @csrf

                                            <div class="form-group">
                                                <label for="inventory_entregar_product_id" class="form-label">Producto</label>
                                                <select id="inventory_entregar_product_id" name="product_id"
                                                    class="form-control" required>
                                                    <option value="">Seleccionar producto</option>
                                                    @foreach ($inventoryProducts as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_entregar_quantity" class="form-label">Cantidad</label>
                                                <input id="inventory_entregar_quantity" name="quantity" type="number"
                                                    step="0.01" min="0.01" class="form-control" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_entregar_note" class="form-label">Nota</label>
                                                <input id="inventory_entregar_note" name="note" type="text"
                                                    class="form-control" placeholder="Opcional">
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-secondary">Registrar entrega</button>
                                            </div>
                                        </form>
                                    </div>
                                </x-card>
                            @endif
                        @endcan

                        <x-card class="list-card">
                            @include('inventory.partials.movements-table', [
                                'movements' => $inventoryMovements,
                                'emptyMessage' => 'No hay movimientos de stock para esta orden.',
                            ])
                        </x-card>
                    </div>
                </section>
            @endif

            @if ($supportsDocumentsModule)
                <section class="tab-panel" data-tab-panel="documents" hidden>
                    <div class="tab-panel-stack">
                        @include('documents.partials.embedded-tabs', [
                            'documents' => $documents,
                            'showParty' => true,
                            'showAsset' => false,
                            'showOrder' => false,
                            'emptyMessage' => 'Esta orden no tiene documentos vinculados.',
                            'tabsId' => 'order-documents-tabs',
                            'trailQuery' => $trailQuery,
                            'order' => $order,
                        ])
                    </div>
                </section>
            @endif

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachable' => $order,
                        'attachableType' => 'order',
                        'attachableId' => $order->id,
                        'trailQuery' => $trailQuery,
                        'tabsId' => 'order-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
