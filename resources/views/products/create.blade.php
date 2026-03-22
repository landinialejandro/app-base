{{-- FILE: resources/views/products/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nuevo producto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('products.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nuevo producto" />

        <x-card>
            <form action="{{ route('products.store', NavigationTrail::toQuery($navigationTrail)) }}" method="POST"
                class="form">
                @include('products._form')
            </form>
        </x-card>
    </x-page>
@endsection
