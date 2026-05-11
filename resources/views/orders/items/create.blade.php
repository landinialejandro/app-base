{{-- FILE: resources/views/orders/items/create.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $showTrail = NavigationTrail::sliceBefore($navigationTrail, 'orders.items.create', $order->id);
        $cancelUrl = route(
            OrderNavigationTrail::showRouteName(request(), $showTrail),
            ['order' => $order] + NavigationTrail::toQuery($showTrail)
        );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('orders.items.store', ['order' => $order] + $trailQuery) }}" class="form">
                @csrf

                @include('orders.items._form', [
                    'supportsProductsModule' => $supportsProductsModule,
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="orders.items.create" version="V6" align="right" />
@endsection