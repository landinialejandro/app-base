{{-- FILE: resources/views/inventory/create-movement.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Agregar movimiento')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = $breadcrumbItems ?? NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = $trailQuery ?? NavigationTrail::toQuery($navigationTrail);
        $cancelUrl =
            $cancelUrl ??
            NavigationTrail::previousUrl(
                $navigationTrail,
                route('inventory.show', ['product' => $product] + $trailQuery),
            );
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar movimiento" />

        <x-card>
            <form action="{{ route('inventory.movements.store', $trailQuery) }}" method="POST" class="form">
                @csrf

                @include('inventory._movement-form', [
                    'product' => $product,
                ])

                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="return_context" value="inventory.show">

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
