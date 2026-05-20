{{-- FILE: resources/views/shops/items/edit.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Editar artículo')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $navigationTrail = $navigationTrail ?? [];
        $trailQuery = $trailQuery ?? NavigationTrail::toQuery($navigationTrail);
    @endphp
    <x-page>

        <x-breadcrumb :items="NavigationTrail::toBreadcrumbItems($navigationTrail)" />

        <x-page-header title="Editar artículo" />

        <x-card>
            <form method="POST" action="{{ route('shops.items.update', ['shop' => $shop, 'item' => $item] + $trailQuery) }}" class="form">
                @csrf
                @method('PUT')

                @include('shops.items._form', [
                    'mode' => 'edit',
                ])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('shops.show', ['shop' => $shop, 'return_tab' => 'items'] + $trailQuery) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection