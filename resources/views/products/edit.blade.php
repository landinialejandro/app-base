{{-- FILE: resources/views/products/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar producto" />

        <x-card>
            <form action="{{ route('products.update', ['product' => $product] + $trailQuery) }}" method="POST" class="form">
                @method('PUT')
                @include('products._form', ['product' => $product])
            </form>
        </x-card>
    </x-page>
@endsection
