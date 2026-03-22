{{-- FILE: resources/views/orders/items/create.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.show', ['order' => $order]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('orders.items.store', ['order' => $order] + $trailQuery) }}" class="form">
                @csrf

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
