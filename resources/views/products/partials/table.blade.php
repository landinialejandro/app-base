{{-- FILE: resources/views/products/partials/table.blade.php | V3 --}}

@php
    use App\Support\Catalogs\ProductCatalog;
    use App\Support\Navigation\NavigationTrail;

    $products = $products ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay productos para mostrar.';
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);
@endphp

@if ($products->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>SKU</th>
                    <th>Precio</th>
                    <th>Unidad</th>
                    <th>Tipo</th>
                    <th>Activo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'products.show',
                                $product->id,
                                $product->name ?: 'Producto #' . $product->id,
                                route('products.show', ['product' => $product]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode('products.index', null, 'Productos', route('products.index')),
                                NavigationTrail::makeNode(
                                    'products.show',
                                    $product->id,
                                    $product->name ?: 'Producto #' . $product->id,
                                    route('products.show', ['product' => $product]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);
                    @endphp

                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>
                            <a href="{{ route('products.show', ['product' => $product] + $rowTrailQuery) }}">
                                {{ $product->name }}
                            </a>
                        </td>
                        <td>{{ $product->sku ?? '—' }}</td>
                        <td>
                            {{ $product->price !== null ? number_format((float) $product->price, 2, ',', '.') : '—' }}
                        </td>
                        <td>{{ $product->unit_label ?? '—' }}</td>
                        <td>{{ ProductCatalog::label($product->kind) }}</td>
                        <td>{{ $product->is_active ? 'Sí' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
