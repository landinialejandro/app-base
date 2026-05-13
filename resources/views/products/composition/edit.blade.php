{{-- FILE: resources/views/products/composition/edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Editar componente')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $showTrail = NavigationTrail::sliceBefore($navigationTrail, 'products.components.edit', $productComponent->id);
        $cancelUrl = route('products.show', [
            'product' => $product,
            'return_tab' => 'product.composition.items',
        ] + NavigationTrail::toQuery($showTrail));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar componente" />

        <x-card>
            <p class="text-muted">
                Producto compuesto: {{ $product->name }}. Esta acción solo modifica la composición del catálogo.
            </p>

            <form
                method="POST"
                action="{{ route('products.components.update', ['product' => $product, 'component' => $productComponent] + $trailQuery) }}"
                class="form"
            >
                @csrf
                @method('PUT')

                @include('products.composition._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="products.composition.edit" version="V3" align="right" />
@endsection