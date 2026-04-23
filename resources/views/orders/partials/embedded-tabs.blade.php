{{-- FILE: resources/views/orders/partials/embedded-tabs.blade.php | V8 --}}

@php
    use App\Models\Order;
    use App\Support\Auth\Security;
    use App\Support\Catalogs\OrderCatalog;

    $orders = $orders ?? collect();

    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay órdenes para mostrar.';
    $allLabel = $allLabel ?? 'Todas';

    $kinds = OrderCatalog::kindLabels();
    $tabsId = $tabsId ?? 'orders-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];

    $allowedCreateKinds = collect(OrderCatalog::kinds())
        ->filter(
            fn(string $kind) => app(Security::class)->allows(auth()->user(), 'orders.create', Order::class, [
                'kind' => $kind,
            ]),
        )
        ->values();

    $canCreateOrders = $allowedCreateKinds->isNotEmpty();
    $defaultCreateKind = $allowedCreateKinds->first();
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarActions = null;

        if ($canCreateOrders) {
            $toolbarActions = route('orders.create', $createBaseQuery + $trailQuery + ['kind' => $defaultCreateKind]);
        }
    @endphp

    <x-tab-toolbar label="Tipos de órdenes">
        <x-slot:tabs>
            <x-horizontal-scroll label="Tipos de órdenes">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    {{ $allLabel }}
                    @if ($orders->count())
                        ({{ $orders->count() }})
                    @endif
                </button>

                @foreach ($kinds as $value => $label)
                    @php
                        $kindOrders = $orders->where('kind', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($kindOrders->count())
                            ({{ $kindOrders->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($toolbarActions)
                <x-button-create :href="$toolbarActions" label="Nueva orden" class="btn-sm">
                </x-button-create>
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('orders.partials.table', [
                    'orders' => $orders,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindOrders = $orders->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('orders.partials.table', [
                        'orders' => $kindOrders,
                        'showParty' => $showParty,
                        'showAsset' => $showAsset,
                        'emptyMessage' => "No hay órdenes de tipo {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
