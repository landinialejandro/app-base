{{-- FILE: resources/views/shops/items/create.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Agregar artículo')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $navigationTrail = $navigationTrail ?? [];
        $trailQuery = $trailQuery ?? NavigationTrail::toQuery($navigationTrail);
    @endphp
    <x-page>

        <x-breadcrumb :items="NavigationTrail::toBreadcrumbItems($navigationTrail)" />

        <x-page-header title="Agregar artículo" />

        <x-card>
            <form method="POST" action="{{ route('shops.items.store', ['shop' => $shop] + $trailQuery) }}" class="form">
                @csrf

                @include('shops.items._form', [
                    'mode' => 'create',
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('shops.show', ['shop' => $shop, 'return_tab' => 'items'] + $trailQuery) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection