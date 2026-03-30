{{-- FILE: resources/views/products/edit.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('products.show', ['product' => $product]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar producto" />

        <x-card>
            <form action="{{ route('products.update', ['product' => $product] + $trailQuery) }}" method="POST" class="form">
                @csrf
                @method('PUT')

                @include('products._form', ['product' => $product])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
