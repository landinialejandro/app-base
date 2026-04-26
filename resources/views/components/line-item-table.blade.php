{{-- FILE: resources/views/components/line-item-table.blade.php | V4 --}}

@props([
    'parent' => null,
    'items' => collect(),
    'emptyMessage' => 'No hay ítems cargados.',
    'trailQuery' => [],
    'catalogClass',
    'parentParamName',
    'editRoute',
    'destroyRoute',
    'rowActionHost' => null,
    'rowActionContextKey' => null,
    'parentReadonly' => false,
    'modalNamespace' => '',
])

@php
    use App\Support\Catalogs\ProductCatalog;
    use App\Support\Inventory\InventorySurfaceService;
    use App\Support\LineItems\LineItemViewHelper;
    use App\Support\Modules\ModuleSurfaceRegistry;

    $viewHelper = app(LineItemViewHelper::class);

    $items = collect($items ?? [])->values();
    $trailQuery = is_array($trailQuery ?? null) ? $trailQuery : [];
    $parentReadonly = (bool) $parentReadonly;
    $canUpdateParent = $parent && auth()->user() && auth()->user()->can('update', $parent);
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Ítem</th>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $lineStatusLabel = $viewHelper->statusLabel($item, $catalogClass);
                        $lineStatusBadge = $viewHelper->statusBadge($item, $catalogClass);

                        $canEdit = $viewHelper->canEdit($item, $catalogClass, $parentReadonly);
                        $canDelete = $viewHelper->canDelete($item, $catalogClass, $parentReadonly);

                        $itemTypeLabel = ProductCatalog::kindLabel($item->kind);
                        $rowActions = collect();

                        if ($parent && $rowActionHost && $rowActionContextKey) {
                            $rowHostPack = app(InventorySurfaceService::class)->hostPack($rowActionHost, $item, [
                                $rowActionContextKey => $parent,
                                'trailQuery' => $trailQuery,
                                'modal_namespace' => $modalNamespace,
                            ]);

                            $rowActions = collect(
                                app(ModuleSurfaceRegistry::class)->linkedFor($rowActionHost, $rowHostPack),
                            )
                                ->where('slot', 'row_actions')
                                ->sortBy(fn($surface) => $surface['priority'] ?? 999)
                                ->values();
                        }

                        $routeParams =
                            [
                                $parentParamName => $parent,
                                'item' => $item,
                            ] + $trailQuery;
                    @endphp

                    <tr>
                        <td>{{ $item->position }}</td>

                        <td>
                            <div>{{ $item->description }}</div>
                            <div class="text-muted">{{ $itemTypeLabel }}</div>
                        </td>

                        <td>
                            <span class="status-badge {{ $lineStatusBadge }}">
                                {{ $lineStatusLabel }}
                            </span>
                        </td>

                        <td>{{ $viewHelper->qty($item->quantity) }}</td>

                        <td>{{ $viewHelper->money($item->unit_price) }}</td>

                        <td>{{ $viewHelper->money($item->subtotal) }}</td>

                        <td class="compact-actions-cell">
                            @if ($canUpdateParent)
                                <div class="compact-actions">
                                    @if ($canEdit)
                                        <x-button-tool :href="route($editRoute, $routeParams)" title="Editar ítem" label="Editar ítem">
                                            <x-icons.pencil />
                                        </x-button-tool>
                                    @endif

                                    @if ($canDelete)
                                        <x-button-tool-submit :action="route($destroyRoute, $routeParams)" method="DELETE" variant="danger"
                                            title="Eliminar ítem" label="Eliminar ítem"
                                            message="¿Deseas eliminar este ítem?">
                                            <x-icons.trash />
                                        </x-button-tool-submit>
                                    @endif

                                    @foreach ($rowActions as $surface)
                                        @include($surface['view'], $surface['data'] ?? [])
                                    @endforeach

                                    @if (!$canEdit && !$canDelete && $rowActions->isEmpty())
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            @elseif ($rowActions->isNotEmpty())
                                <div class="compact-actions">
                                    @foreach ($rowActions as $surface)
                                        @include($surface['view'], $surface['data'] ?? [])
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif

<x-dev-component-version name="line-item-table" version="V4" />