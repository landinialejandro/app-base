{{-- FILE: resources/views/orders/edit.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Editar orden')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.show', ['order' => $order]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar orden" />

        <x-card>
            <form method="POST" action="{{ route('orders.update', ['order' => $order] + $trailQuery) }}" class="form">
                @csrf
                @method('PUT')

                @include('orders._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
