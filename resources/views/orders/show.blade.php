{{-- FILE: resources/views/orders/show.blade.php | V18 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;

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

        $canViewLinkedParty = $supportsPartiesModule && $order->party && $user && $user->can('view', $order->party);

        $canViewLinkedAsset = $supportsAssetsModule && $order->asset && $user && $user->can('view', $order->asset);

        $canViewLinkedTask = $supportsTasksModule && $order->task && $user && $user->can('view', $order->task);

        $canViewLinkedDocuments = $supportsDocumentsModule;
        $canViewLinkedProducts = $supportsProductsModule;
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
                @if ($canViewLinkedParty)
                    <a href="{{ route('parties.show', ['party' => $order->party] + $trailQuery) }}">
                        {{ $order->party->name }}
                    </a>
                @else
                    {{ $order->party?->name ?: '—' }}
                @endif
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

                @if ($supportsAssetsModule)
                    <x-show-summary-item-detail-block label="Activo">
                        @if ($canViewLinkedAsset)
                            <a href="{{ route('assets.show', ['asset' => $order->asset] + $trailQuery) }}">
                                {{ $order->asset->name }}
                            </a>
                        @else
                            {{ $order->asset?->name ?: '—' }}
                        @endif
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

        <div class="tabs" data-tabs>
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
                                                        <option value="{{ $product->id }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_consumir_quantity" class="form-label">Cantidad</label>
                                                <input type="number" step="0.01" min="0.01"
                                                    id="inventory_consumir_quantity" name="quantity" class="form-control"
                                                    required>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_consumir_notes" class="form-label">Notas</label>
                                                <textarea id="inventory_consumir_notes" name="notes" rows="2" class="form-control"></textarea>
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-warning">Registrar consumo</button>
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
                                                        <option value="{{ $product->id }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_entregar_quantity" class="form-label">Cantidad</label>
                                                <input type="number" step="0.01" min="0.01"
                                                    id="inventory_entregar_quantity" name="quantity" class="form-control"
                                                    required>
                                            </div>

                                            <div class="form-group">
                                                <label for="inventory_entregar_notes" class="form-label">Notas</label>
                                                <textarea id="inventory_entregar_notes" name="notes" rows="2" class="form-control"></textarea>
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-success">Registrar entrega</button>
                                            </div>
                                        </form>
                                    </div>
                                </x-card>
                            @endif
                        @endcan

                        <x-card>
                            @if ($inventoryMovements->count())
                                <div class="table-wrap">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Documento</th>
                                                <th>Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($inventoryMovements as $movement)
                                                @php
                                                    $canViewMovementProduct =
                                                        $canViewLinkedProducts &&
                                                        $movement->product &&
                                                        $user &&
                                                        $user->can('view', $movement->product);

                                                    $canViewMovementDocument =
                                                        $canViewLinkedDocuments &&
                                                        $movement->document &&
                                                        $user &&
                                                        $user->can('view', $movement->document);
                                                @endphp
                                                <tr>
                                                    <td>{{ $movement->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                                    <td>{{ ucfirst($movement->kind) }}</td>
                                                    <td>
                                                        @if ($canViewMovementProduct)
                                                            <a
                                                                href="{{ route('products.show', ['product' => $movement->product] + $trailQuery) }}">
                                                                {{ $movement->product->name }}
                                                            </a>
                                                        @elseif ($movement->product)
                                                            {{ $movement->product->name }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                                                    <td>
                                                        @if ($canViewMovementDocument)
                                                            <a
                                                                href="{{ route('documents.show', ['document' => $movement->document] + $trailQuery) }}">
                                                                {{ $movement->document->number ?: 'Documento #' . $movement->document->id }}
                                                            </a>
                                                        @elseif ($movement->document)
                                                            {{ $movement->document->number ?: 'Documento #' . $movement->document->id }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ $movement->notes ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mb-0">No hay movimientos de stock registrados para esta orden.</p>
                            @endif
                        </x-card>
                    </div>
                </section>
            @endif

            @if ($supportsDocumentsModule)
                <section class="tab-panel" data-tab-panel="documents" hidden>
                    <div class="tab-panel-stack">
                        <x-card>
                            @if ($documents->count())
                                <div class="table-wrap">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Número</th>
                                                <th>Tipo</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documents as $document)
                                                @php
                                                    $canViewDocument = $user && $user->can('view', $document);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if ($canViewDocument)
                                                            <a
                                                                href="{{ route('documents.show', ['document' => $document] + $trailQuery) }}">
                                                                {{ $document->number ?: 'Documento #' . $document->id }}
                                                            </a>
                                                        @else
                                                            {{ $document->number ?: 'Documento #' . $document->id }}
                                                        @endif
                                                    </td>
                                                    <td>{{ DocumentCatalog::kindLabel($document->kind) }}</td>
                                                    <td>{{ DocumentCatalog::statusLabel($document->status) }}</td>
                                                    <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                                                    <td>${{ number_format((float) $document->total, 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mb-0">No hay documentos asociados a esta orden.</p>
                            @endif
                        </x-card>
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
                        'navigationTrail' => $navigationTrail,
                        'tabsId' => 'order-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
