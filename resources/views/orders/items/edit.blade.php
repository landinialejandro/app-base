{{-- FILE: resources/views/orders/items/edit.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Editar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.show', ['order' => $order]));
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

                @include('orders.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
