{{-- FILE: resources/views/orders/edit.blade.php | V10 --}}

@extends('layouts.app')

@section('title', 'Editar orden')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $showTrail = NavigationTrail::sliceBefore($navigationTrail, 'orders.edit', $order->id);
        $backUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            route(
                OrderNavigationTrail::showRouteName(request(), $showTrail),
                ['order' => $order] + NavigationTrail::toQuery($showTrail)
            )
        );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar orden">
            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('orders.update', ['order' => $order] + $trailQuery) }}">
                @csrf
                @method('PUT')

                @include('orders._form', [
                    'order' => $order,
                    'relationshipBoundary' => $relationshipBoundary,
                ])

                <div class="form-actions">
                    <x-button-primary type="submit">
                        Guardar cambios
                    </x-button-primary>

                    <x-button-secondary :href="$backUrl">
                        Cancelar
                    </x-button-secondary>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="orders.edit" version="V10" align="right" />
@endsection