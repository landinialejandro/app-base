{{-- FILE: resources/views/orders/create.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Nueva orden')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nueva orden" />

        <x-card>
            <form method="POST" action="{{ route('orders.store', $trailQuery) }}" class="form">
                @csrf

                @include('orders._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
