{{-- FILE: resources/views/products/partials/table.blade.php --}}

@php
    use App\Support\Catalogs\ProductCatalog;

    $products = $products ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay productos para mostrar.';
@endphp

@if ($products->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID!</th>
                    <th>Nombre</th>
                    <th>SKU</th>
                    <th>Precio</th>
                    <th>Unidad</th>
                    <th>Tipo</th>
                    <th>Activo</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>
                            <a href="{{ route('products.show', $product) }}">
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
                        <td>{{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
