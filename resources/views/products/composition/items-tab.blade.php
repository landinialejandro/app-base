{{-- FILE: resources/views/products/composition/items-tab.blade.php | V7 --}}

@php
    $product = $product ?? null;

    $composition = $composition ?? [
        'has_components' => false,
        'components_count' => 0,
        'components' => collect(),
    ];

    $trailQuery = $trailQuery ?? [];
    $tabQuery = ['return_tab' => 'product.composition.items'] + $trailQuery;

    $components = collect($composition['components'] ?? []);

    $canUpdateComposition = $product && auth()->user()?->can('update', $product);
@endphp

<x-tab-toolbar label="Composición" context="Receta del producto. No mueve stock ni ejecuta producción.">
    @if ($canUpdateComposition)
        <x-slot:actions>
            <x-button-create :href="route('products.components.create', ['product' => $product] + $tabQuery)" label="Agregar componente" class="btn-sm" />
        </x-slot:actions>
    @endif
</x-tab-toolbar>

<x-card class="list-card">

    @if ($components->isNotEmpty())
        <div class="table-wrap list-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>SKU</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Requerido</th>
                        <th>Orden</th>
                        @if ($canUpdateComposition)
                            <th>Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($components as $compositionComponent)
                        @php
                            $componentProduct = $compositionComponent['product'] ?? [];
                            $linked = $componentProduct['linked'] ?? [];
                            $showUrl = $linked['show_url'] ?? null;
                            $name = $componentProduct['name'] ?? 'Componente';
                            $sku = $componentProduct['sku'] ?? '—';
                        @endphp

                        <tr>
                            <td>
                                @if ($showUrl)
                                    <a href="{{ $showUrl }}">{{ $name }}</a>
                                @else
                                    {{ $name }}
                                @endif
                            </td>
                            <td>{{ $sku }}</td>
                            <td>{{ number_format((float) ($compositionComponent['quantity'] ?? 0), 4, ',', '.') }}</td>
                            <td>{{ $compositionComponent['unit_label'] ?? '—' }}</td>
                            <td>{{ $compositionComponent['is_required'] ?? false ? 'Sí' : 'No' }}</td>
                            <td>{{ $compositionComponent['sort_order'] ?? '—' }}</td>

                            @if ($canUpdateComposition)
                                <td>
                                    <div class="actions-inline">
                                        <x-button-tool :href="route(
                                            'products.components.edit',
                                            ['product' => $product, 'component' => $compositionComponent['id']] +
                                                $tabQuery,
                                        )" title="Editar componente"
                                            label="Editar componente">
                                            <x-icons.pencil />
                                        </x-button-tool>

                                        <x-button-tool-submit :action="route(
                                            'products.components.destroy',
                                            ['product' => $product, 'component' => $compositionComponent['id']] +
                                                $tabQuery,
                                        )" method="DELETE" variant="danger"
                                            title="Eliminar componente" label="Eliminar componente"
                                            message="¿Eliminar este componente de la composición?">
                                            <x-icons.trash />
                                        </x-button-tool-submit>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="mb-0">Este producto todavía no tiene componentes definidos.</p>
    @endif
</x-card>

<x-dev-component-version name="products.composition.items-tab" version="V7" />
