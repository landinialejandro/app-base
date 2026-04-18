{{-- FILE: resources/views/orders/show.blade.php | V36 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Appointments\AppointmentLinkedAction;
        use App\Support\Assets\AssetLinkedAction;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Orders\OrderSurfaceService;
        use App\Support\Parties\PartyLinkedAction;

        $items = $order->items ?? collect();

        $user = auth()->user();

        $supportsProductsModule = $supportsProductsModule ?? true;
        $supportsTasksModule = $supportsTasksModule ?? true;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));

        $canViewLinkedTask = $supportsTasksModule && $order->task && $user && $user->can('view', $order->task);

        $partyAction = PartyLinkedAction::forParty($order->party, $trailQuery, 'Contacto');
        $assetAction = AssetLinkedAction::forAsset($order->asset, $trailQuery, 'Activo');
        $appointmentAction = AppointmentLinkedAction::forOrder($order, $trailQuery, true);

        $hostPack = app(OrderSurfaceService::class)->hostPack('orders.show', $order, [
            'trailQuery' => $trailQuery,
        ]);

        $embeddedTabs = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('orders.show', $hostPack))->values();
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

                        @foreach ($embeddedTabs as $embeddedTab)
                            <button type="button" class="tabs-link" data-tab-link="{{ $embeddedTab['key'] }}"
                                role="tab" aria-selected="false">
                                {{ $embeddedTab['label'] ?? $embeddedTab['key'] }}
                                @if (array_key_exists('count', $embeddedTab) && (int) $embeddedTab['count'] > 0)
                                    ({{ $embeddedTab['count'] }})
                                @endif
                            </button>
                        @endforeach
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

            @foreach ($embeddedTabs as $embeddedTab)
                <section class="tab-panel" data-tab-panel="{{ $embeddedTab['key'] }}" hidden>
                    <div class="tab-panel-stack">
                        @include($embeddedTab['view'], $embeddedTab['data'] ?? [])
                    </div>
                </section>
            @endforeach
        </div>
    </x-page>
@endsection
