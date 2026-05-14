{{-- FILE: resources/views/self-service-sales/shop.blade.php | V9 --}}

@php($publicPage = true)

@extends('layouts.app')

@section('title', 'Tienda')

@section('content')
    <x-page>
        <div class="welcome-page">
            <div class="public-panel">
                <x-page-header
                    title="Tienda"
                    subtitle="{{ $tenant->name }}"
                    vertical="vertical"
                />

                <x-card>
                    @php($activeShop = $activeShop ?? null)
                    @php($shopItems = $shopItems ?? collect())
                    @php($shopCatalogStatus = $shopCatalogStatus ?? 'hidden_until_enabled')

                    @if(session('self_service_sales_operation_notice') && ! $externalCustomer)
                        <div class="detail-block">
                            <strong>Operación pendiente</strong>
                            <p>
                                {{ session('self_service_sales_operation_notice') }}
                            </p>
                        </div>
                    @endif

                    <p>
                        Esta es la tienda autogestiva de {{ $tenant->name }}.
                    </p>

                    @if($externalCustomer)
                        <div class="detail-block">
                            <strong>Ingresaste como cliente externo</strong>

                            <p>
                                Estás viendo esta tienda con un acceso externo activo.
                            </p>

                            <p>
                                Cuenta externa: {{ $externalCustomer['display_name'] }}.
                            </p>

                            <p>
                                Cliente vinculado: {{ $externalCustomer['party_label'] }}.
                            </p>

                            <p>
                                Identidad: {{ $externalCustomer['identity_label'] }}.
                            </p>

                            <p>
                                Operación:
                                <span class="status-badge {{ $externalCustomer['operation_enabled'] ? 'status-badge--done' : 'status-badge--pending' }}">
                                    {{ $externalCustomer['operation_enabled'] ? 'Habilitada' : 'Pendiente' }}
                                </span>
                            </p>

                            @if(! $externalCustomer['can_operate'])
                                <p>
                                    Tu cuenta externa está reconocida para esta tienda, pero la operación comercial todavía
                                    no está habilitada. Por ahora no podés comprar saldo, usar fichas, operar con QR,
                                    realizar pagos ni consultar movimientos comerciales reales desde este acceso.
                                </p>
                            @else
                                <p>
                                    Tu cuenta externa está habilitada para operar en esta tienda según las condiciones
                                    comerciales vigentes. En este corte podés ver el catálogo publicado inicial, sin
                                    compra ni confirmación transaccional todavía.
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="detail-block">
                            <strong>Estás viendo la tienda como visitante</strong>

                            <p>
                                Podés conocer este espacio público de {{ $tenant->name }} sin iniciar acceso externo.
                            </p>

                            <p>
                                Para vincularte como cliente de esta tienda, podés registrarte o ingresar con una cuenta
                                externa ya habilitada.
                            </p>
                        </div>
                    @endif

                    <div class="detail-block">
                        <strong>Estado actual de la tienda</strong>

                        <p>
                            La tienda pública está disponible como punto de acceso externo y catálogo publicado inicial.
                        </p>

                        <p>
                            La operación comercial completa todavía no está habilitada en este corte. No se generan
                            compras, pagos, órdenes, documentos, movimientos de stock, fichas ni QR desde esta pantalla.
                        </p>
                    </div>

                    @if($externalCustomer && $externalCustomer['can_operate'])
                        <div class="detail-block">
                            <strong>Autoservicio</strong>

                            @if($shopItems->isEmpty())
                                @if($shopCatalogStatus === 'without_active_shop')
                                    <p>
                                        Esta tienda todavía no tiene una configuración activa publicada.
                                    </p>
                                @else
                                    <p>
                                        Esta tienda activa todavía no tiene artículos publicados.
                                    </p>
                                @endif
                            @else
                                @if($activeShop)
                                    <div class="visual-title">
                                        {{ $activeShop->name }}
                                    </div>

                                    @if($activeShop->description)
                                        <p>
                                            {{ $activeShop->description }}
                                        </p>
                                    @endif
                                @endif

                                <p>
                                    Catálogo publicado inicial. Selección de productos en preparación.
                                </p>

                                <div class="visual-grid">
                                    @foreach($shopItems as $shopItem)
                                        <div class="visual-card">
                                            <div class="visual-title">
                                                {{ $shopItem->displayName() }}
                                            </div>

                                            @if($shopItem->displayDescription())
                                                <p>
                                                    {{ $shopItem->displayDescription() }}
                                                </p>
                                            @endif

                                            <p>
                                                @if($shopItem->displayPrice() !== null)
                                                    $ {{ number_format((float) $shopItem->displayPrice(), 2, ',', '.') }}
                                                @else
                                                    Precio a confirmar
                                                @endif

                                                @if($shopItem->product?->unit_label)
                                                    · {{ $shopItem->product->unit_label }}
                                                @endif
                                            </p>

                                            <button type="button" class="btn btn-secondary" disabled>
                                                Operación comercial en preparación
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="form-actions">
                        @if(! $externalCustomer)
                            <a href="{{ route('self_service_sales.register.create', ['tenant' => $tenant]) }}" class="btn btn-primary">
                                Registrarme como cliente
                            </a>

                            <a href="{{ route('self_service_sales.access') }}" class="btn btn-secondary">
                                Ya tengo cuenta externa
                            </a>
                        @else
                            @if($externalCustomer['can_complete_identity'] ?? false)
                                <a href="{{ route('self_service_sales.identity.edit', ['tenant' => $tenant]) }}" class="btn btn-primary">
                                    Completar mis datos
                                </a>
                            @endif

                            <a href="{{ route('self_service_sales.access') }}" class="btn btn-secondary">
                                Cambiar tienda o cuenta externa
                            </a>

                            <form method="POST" action="{{ route('self_service_sales.logout') }}">
                                @csrf

                                <button type="submit" class="btn btn-secondary">
                                    Salir del acceso externo
                                </button>
                            </form>
                        @endif
                    </div>
                </x-card>

                <x-dev-component-version name="self-service-sales.shop" version="V9" align="right" />
            </div>
        </div>
    </x-page>
@endsection
