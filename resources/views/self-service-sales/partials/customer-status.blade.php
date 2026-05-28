{{-- FILE: resources/views/self-service-sales/partials/customer-status.blade.php | V3 --}}

@php
    $tokenPockets = $tokenPockets ?? [];
@endphp

<section class="shop-status-panel">
    @if(session('self_service_sales_operation_notice') && ! $externalCustomer)
        <div class="shop-notice">
            {{ session('self_service_sales_operation_notice') }}
        </div>
    @endif

    @if($externalCustomer)
        <div class="shop-status-panel__line">
            <strong>{{ $externalCustomer['display_name'] }}</strong>
            <span>·</span>
            <span>{{ \App\Support\Catalogs\SelfServiceStoreCustomerCatalog::operationLabel((bool) $externalCustomer['operation_enabled']) }}</span>
        </div>

        @if(! empty($tokenPockets))
            <div class="shop-status-panel__line">
                <strong>Pocket</strong>
                <span>·</span>
                <span>
                    @foreach($tokenPockets as $tokenPocket)
                        @if(! $loop->first)
                            <br>
                        @endif
                        {{ $tokenPocket['name'] }}: {{ $tokenPocket['summary_label'] }}
                    @endforeach
                </span>
            </div>
        @endif

        <div class="shop-status-panel__actions">
            @if($externalCustomer['can_complete_identity'] ?? false)
                <a href="{{ route('self_service_sales.identity.edit', ['tenant' => $tenant]) }}">
                    Completar
                </a>
            @endif

            <a href="{{ route('self_service_sales.access') }}">
                Cambiar
            </a>

            <form method="POST" action="{{ route('self_service_sales.logout') }}">
                @csrf
                <button type="submit">Salir</button>
            </form>
        </div>
    @else
        <div class="shop-status-panel__line">
            <strong>Visitante</strong>
        </div>

        <div class="shop-status-panel__actions">
            <a href="{{ route('self_service_sales.register.create', ['tenant' => $tenant]) }}">
                Registrarme
            </a>

            <a href="{{ route('self_service_sales.access') }}">
                Ingresar
            </a>
        </div>
    @endif
</section>
