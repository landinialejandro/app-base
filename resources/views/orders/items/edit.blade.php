{{-- FILE: resources/views/orders/items/edit.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Editar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $showTrail = NavigationTrail::sliceBefore($navigationTrail, 'orders.items.edit', $item->id);
        $cancelUrl = route(
            OrderNavigationTrail::showRouteName(request(), $showTrail),
            ['order' => $order] + NavigationTrail::toQuery($showTrail)
        );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar ítem" />

        <x-card>
            <form method="POST"
                action="{{ route('orders.items.update', ['order' => $order, 'item' => $item] + $trailQuery) }}"
                class="form">
                @csrf
                @method('PUT')

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

    <x-dev-component-version name="orders.items.edit" version="V6" align="right" />
@endsection