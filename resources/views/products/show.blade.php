{{-- FILE: resources/views/products/show.blade.php | V12 --}}

@extends('layouts.app')

@section('title', 'Detalle del producto')

@section('content')
    @php
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Navigation\NavigationTrail;

        $attachments = $product->attachments ?? collect();
        $inventoryMovements = ($inventoryMovements ?? collect())->values();
        $currentStock = isset($currentStock) ? (float) $currentStock : 0;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('products.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del producto">
            @can('update', $product)
                <a href="{{ route('products.edit', ['product' => $product] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $product)
                <form method="POST" action="{{ route('products.destroy', ['product' => $product] + $trailQuery) }}"
                    class="inline-form" data-action="app-confirm-submit" data-confirm-message="¿Eliminar producto?">
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

        <x-show-summary details-id="product-more-detail">
            <x-show-summary-item label="Nombre">
                {{ $product->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Precio">
                {{ $product->price !== null ? '$' . number_format((float) $product->price, 2, ',', '.') : '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Unidad">
                {{ $product->unit_label ?? '—' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ ProductCatalog::label($product->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    <span class="status-badge {{ $product->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                        {{ $product->is_active ? 'Sí' : 'No' }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="SKU">
                    {{ $product->sku ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $product->updated_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $product->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-card>
            <div class="detail-grid">
                <div class="detail-block">
                    <div class="detail-label">Stock actual</div>
                    <div class="detail-value">{{ number_format($currentStock, 2, ',', '.') }}</div>
                </div>

                <div class="detail-block">
                    <div class="detail-label">Último movimiento</div>
                    <div class="detail-value">
                        @if ($inventoryMovements->isNotEmpty())
                            {{ $inventoryMovements->first()->created_at?->format('d/m/Y H:i') ?? '—' }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            @can('update', $product)
                @if ($product->kind === ProductCatalog::KIND_PRODUCT)
                    <form action="{{ route('products.inventory.ingresar', ['product' => $product] + $trailQuery) }}"
                        method="POST" class="form">
                        @csrf

                        <div class="form-group">
                            <label for="inventory_ingresar_quantity" class="form-label">Ingresar stock</label>
                            <input type="number" step="0.01" min="0.01" id="inventory_ingresar_quantity" name="quantity"
                                class="form-control" value="{{ old('quantity') }}" required>
                            @error('quantity')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="inventory_ingresar_notes" class="form-label">Notas</label>
                            <textarea id="inventory_ingresar_notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="form-help is-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Registrar ingreso</button>
                        </div>
                    </form>
                @endif
            @endcan
        </x-card>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones del producto">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones del producto">
                        <button type="button" class="tabs-link is-active" data-tab-link="inventory" role="tab"
                            aria-selected="true">
                            Movimientos
                            @if ($inventoryMovements->count())
                                ({{ $inventoryMovements->count() }})
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

            <section class="tab-panel is-active" data-tab-panel="inventory">
                <div class="tab-panel-stack">
                    <x-card>
                        @if ($inventoryMovements->count())
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Orden</th>
                                            <th>Documento</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($inventoryMovements as $movement)
                                            <tr>
                                                <td>{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td>{{ ucfirst($movement->kind) }}</td>
                                                <td>{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                                                <td>
                                                    @if ($movement->order)
                                                        <a
                                                            href="{{ route('orders.show', ['order' => $movement->order] + $trailQuery) }}">
                                                            {{ $movement->order->number ?: 'Orden #' . $movement->order->id }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($movement->document)
                                                        <a
                                                            href="{{ route('documents.show', ['document' => $movement->document] + $trailQuery) }}">
                                                            {{ $movement->document->number ?: 'Documento #' . $movement->document->id }}
                                                        </a>
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
                            <p class="mb-0">No hay movimientos de stock registrados para este producto.</p>
                        @endif
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachableType' => 'product',
                        'attachableId' => $product->id,
                        'trailQuery' => $trailQuery,
                        'navigationTrail' => $navigationTrail,
                        'tabsId' => 'product-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
